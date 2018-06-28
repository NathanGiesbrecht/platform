<?php

namespace SuperV\Platform\Domains\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use SuperV\Platform\Domains\Table\Jobs\LoadPagination;
use SuperV\Platform\Domains\Table\Jobs\SetTableEntries;
use SuperV\Platform\Domains\Table\Jobs\SetTableModel;
use SuperV\Platform\Support\Concerns\FiresCallbacks;

class TableBuilder
{
    use DispatchesJobs;
    use FiresCallbacks;

    /**
     * @var \SuperV\Platform\Domains\Table\Table
     */
    protected $table;

    protected $model;

    protected $columns = [];

    protected $filters = [];

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function build()
    {
        $this->dispatch(new SetTableModel($this));

        $this->dispatch(new SetTableEntries($this));
        $this->dispatch(new LoadPagination($this));
    }

    public function getData()
    {
        return [
            'total' => [
                'results' => $this->table->getOption('total_results'),
            ],
            'rows'  => $this->table->getEntries(),
            'pagination' => $this->table->getData()
        ];
    }

    public function getConfig()
    {
        return [
            'config' => [
                'cols'    => $this->getColumns(),
                'filters' => $this->getFilters(),
            ],
        ];
    }

    /**
     * @param string $model
     * @return TableBuilder
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param mixed $columns
     * @return TableBuilder
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function applyFilters(Builder $query)
    {
        foreach ($this->getFilters() as $filter) {
            $filterAttr = $filter['attr'];
            if ($filterValue = request($filterAttr)) {
                if ($callback = array_get($filter, 'callback')) {
                    $callback($query, $filterValue);
                } else {
                    $filterType = $filter['type'];
                    if ($filterType === 'select') {
                        $query->where($filterAttr, $filterValue);
                    } elseif ($filterType === 'text') {
                        $query->where($filterAttr, 'LIKE', "%{$filterValue}%");
                    }
                }
            }
        }
    }

    /**
     * @return \SuperV\Platform\Domains\Table\Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }
}