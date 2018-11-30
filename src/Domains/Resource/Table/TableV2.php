<?php

namespace SuperV\Platform\Domains\Resource\Table;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Contracts\Filter\Filter;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesQuery;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesUIComponent;
use SuperV\Platform\Domains\Resource\Field\Contracts\Field;
use SuperV\Platform\Domains\Resource\Field\FieldComposer;
use SuperV\Platform\Domains\Resource\Filter\ApplyFilters;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Domains\Resource\Table\Contracts\DataProvider;
use SuperV\Platform\Domains\UI\Components\Component;
use SuperV\Platform\Domains\UI\Components\ComponentContract;
use SuperV\Platform\Support\Composer\Composable;
use SuperV\Platform\Support\Composer\Composition;
use SuperV\Platform\Support\Composer\Tokens;
use SuperV\Platform\Support\Concerns\FiresCallbacks;
use SuperV\Platform\Support\Concerns\HasOptions;

class TableV2 implements Composable, ProvidesUIComponent, Responsable
{
    use HasOptions;
    use FiresCallbacks;

    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $resource;

    /** @var Builder */
    protected $query;

    /** @var \SuperV\Platform\Domains\Resource\Table\TableRow|\Illuminate\Support\Collection */
    protected $rows;

    /** @var array */
    protected $pagination;

    /**
     * @var \SuperV\Platform\Domains\Resource\Table\Contracts\DataProvider
     */
    protected $provider;

    protected $actions = [];

    protected $contextActions = [];

    protected $dataUrl;

    protected $mergeFields;

    public function __construct(DataProvider $provider)
    {
        $this->options = collect();
        $this->rows = collect();
        $this->provider = $provider;
    }

    public function mergeFields($fields)
    {
        $this->mergeFields = $fields;

        return $this;
    }

    public function build(Request $request): self
    {
        $fields = $this->resource->fields()
                                 ->forTable()
                                 ->merge($this->copyMergeFields())
                                 ->map(function (Field $field) {
                                     if ($callback = $field->getCallback('table.querying')) {
                                         $this->on('querying', $callback);
                                     }

                                     return $field;
                                 });

        $query = $this->getQuery();
        $this->fire('querying', ['query' => $query]);

        ApplyFilters::dispatch($this->resource->getFilters(), $query,  $request);

        $this->provider->setQuery($query);
        $this->provider->setRowsPerPage($this->getOption('limit', 10));
        $this->provider->fetch();

        $this->pagination = $this->provider->getPagination();

        $this->rows = $this->provider->getEntries()->map(
            function (EntryContract $entry) use ($fields) {
                return [
                    'id'      => $entry->getId(),
                    'fields'  => $fields->map(function (Field $field) use ($entry) {
                        return (new FieldComposer($field))->forTableRow($entry);
                    })->values(),
                    'actions' => ['view'],
                ];
            });

        return $this;
    }

    protected function copyMergeFields()
    {
        return wrap_collect($this->mergeFields)
            ->map(function (Field $field) {
                return clone $field;
            });
    }

    public function addAction($action)
    {
        $this->actions[] = $action;

        return $this;
    }

    public function addContextAction($action)
    {
        $this->contextActions[] = $action;

        return $this;
    }

    public function compose(Tokens $tokens = null)
    {
        return [
            'rows'       => $this->getRows(),
            'pagination' => $this->getPagination(),
        ];
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function getQuery()
    {
        if (! $this->query) {
            $this->query = $this->resource->newQuery();
        }

        if ($this->query instanceof ProvidesQuery) {
            return $this->query->newQuery();
        }

        return $this->query;
    }

    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getPagination(): array
    {
        return $this->pagination;
    }

    public function setResource(Resource $resource): TableV2
    {
        $this->resource = $resource;

        return $this;
    }

    public function makeComponent(): ComponentContract
    {
        return Component::make('sv-table')->card()->setProps($this->composeConfig());
    }

    public function composeConfig()
    {
        $fields = $this->resource
            ->fields()
            ->forTable()
            ->merge($this->copyMergeFields())
            ->map(function (Field $field) {
                return (new FieldComposer($field))->forTableConfig();
            })
            ->values();

        $filters = $this->resource->getFilters()
                                  ->map(function(Filter $filter) {
                                      return (new FieldComposer($filter))->forForm();
                                  });

        $composition = new Composition([
            'config' => [
                'data_url'        => $this->dataUrl ?? sv_url($this->resource->route('index.table').'/data'),
                'fields'          => $fields,
                'filters'          => $filters,
                'row_actions'     => collect($this->actions)->map(function ($action) {
                    if (is_string($action)) {
                        $action = $action::make();
                    }

                    return $action;
                }),
                'context_actions' => collect($this->contextActions)->map(function ($action) {
                    if (is_string($action)) {
                        $action = $action::make();
                    }

                    return $action;
                }),

            ],
        ]);

        return $composition->get();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return response()->json([
            'data' => sv_compose($this, $this->makeTokens()),
        ]);
    }

    /**
     * @return array
     */
    protected function makeTokens(): array
    {
        return [];
    }

    public function setDataUrl($dataUrl)
    {
        $this->dataUrl = $dataUrl;

        return $this;
    }

    public function setActions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function uuid()
    {
        return $this->config->uuid();
    }
}