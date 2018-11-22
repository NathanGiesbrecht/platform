<?php

namespace SuperV\Platform\Domains\Resource\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Field\Field;
use SuperV\Platform\Domains\Resource\Table\Contracts\DataProvider;
use SuperV\Platform\Support\Concerns\HasOptions;

class Table
{
    use HasOptions;

    /**
     * @var TableConfig
     */
    protected $config;

    /** @var Builder */
    protected $query;

    /** @var \SuperV\Platform\Domains\Resource\Table\TableRow|\Illuminate\Support\Collection */
    protected $rows;

    /** @var \SuperV\Platform\Domains\Resource\Field\Field[]|\Illuminate\Support\Collection */
    protected $fields;

    /** @var array */
    protected $pagination;

    /**
     * @var \SuperV\Platform\Domains\Resource\Table\Contracts\DataProvider
     */
    protected $provider;

    public function __construct(DataProvider $provider)
    {
        $this->options = collect();
        $this->rows = collect();
        $this->provider = $provider;
    }

    public function build(): self
    {
        $this->fields = $this->config->getFields()->map(function (Field $field) {
            if ($callback = $field->getAlterQueryCallback()) {
                $callback($this->getQuery());
            }

            return $field;
        });

        $entries = $this->fetch();

        $this->rows = $this->buildRows($entries);

        return $this;
    }

    protected function buildRows(Collection $entries)
    {
        $rows = collect();
        $entries->map(
            function (EntryContract $entry) use ($rows) {
                $row = new TableRow($this, $entry);
                $rows->push($row->build());
            });

        return $rows;
    }

    /**
     * @param $query
     */
    protected function fetch(): Collection
    {
        $this->provider->setQuery($this->getQuery());
        $this->provider->setRowsPerPage($this->getOption('limit', 10));
        $this->provider->fetch();

        $this->pagination = $this->provider->getPagination();

        return $this->provider->getEntries();
    }

    public function url()
    {
        return $this->config->getDataUrl();
    }

    public function compose(): array
    {
        return (new TableData($this))->toArray();
    }

    public function getConfig(): TableConfig
    {
        return $this->config;
    }

    public function setConfig(TableConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getActions(): Collection
    {
        return $this->config->getRowActions();
    }

    public function getQuery()
    {
        if (! $this->query) {
            $this->query = $this->config->newQuery();
        }

        $this->applyQueryParams();

        return $this->query;
    }

    public function setQuery($query): Table
    {
        $this->query = $query;

        return $this;
    }

    protected function applyQueryParams()
    {
        if (! $params = $this->config->getQueryParams()) {
            return;
        }

        foreach (array_get($params, 'joins', []) as $join) {
            $this->query->join(
                $join['table'], $join['first'], $join['operator'], $join['second'], $join['type']
            );
        }
        foreach (array_get($params, 'wheres', []) as $where) {
            $this->query->where(
                $where['column'],
                $where['operator'],
                $where['value'],
                $where['boolean'] ?? 'and'
            );
        }
    }

    public function getPagination(): array
    {
        return $this->pagination;
    }

    public function uuid()
    {
        return $this->config->uuid();
    }

    public static function config(TableConfig $config): self
    {
        return (new Table(new EloquentDataProvider))->setConfig($config);
    }
}