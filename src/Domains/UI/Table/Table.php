<?php namespace SuperV\Platform\Domains\UI\Table;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Input;
use SuperV\Platform\Domains\Entry\EntryCollection;
use SuperV\Platform\Support\Collection;

class Table
{
    /** @var array */
    protected $data;

    protected $entries;

    protected $columns;

    protected $rows;

    protected $buttons;

    protected $actions;

    protected $options;

    protected $view = 'superv::table.table';

    protected $viewVars = [];

    public function __construct(
        EntryCollection $entries,
        RowCollection $rows,
        Collection $data,
        Collection $options
    )
    {
        $this->entries = $entries;
        $this->data = $data;
        $this->options = $options;
        $this->rows = $rows;
    }

    public function addColumn()
    {
        $model = $this->entries->first();

        $new_column = forward_static_call_array([new Column(), 'create'], func_get_args());

        $new_column->setOptionsFromModel($model);

        $this->columns[] =& $new_column;

        return $new_column;
    }

    public function addButton(array $params)
    {
        $this->buttons[] = $params;
    }

    public function setButtons($buttons)
    {
        $this->buttons = $buttons;
    }

    protected function addColumns($columns)
    {
        $model = $this->entries->first();

        if ($columns) {
            foreach ($columns as $key => $field) {
                if (is_numeric($key)) {
                    // Simple non-keyed array passed.
                    $new_column = Column::create($field);
                } else {
                    // Key also matters, apparently
                    $new_column = Column::create($key, $field);
                }

                $new_column->setOptionsFromModel($model);

                $this->columns[] = $new_column;
            }
        }
    }

    /**
     * Render the table view file.
     * @return array
     */
    public function render()
    {
        $this->appendPaginationLinks();

        $viewData = array_merge($this->viewVars, [
            'rows'    => $this->getRows(),
            'columns' => $this->getColumns(),
            'buttons' => $this->buttons,
            'actions' => $this->actions,
            'table'   => $this,
        ]);

        return view($this->view, $viewData)->render();
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns)
    {
        $this->clearColumns();
        $this->addColumns($columns);
    }

    public function setEntries($entries)
    {
        if ($entries instanceof LengthAwarePaginator) {
            $entries = new EntryCollection($entries->items());
        }

        $this->entries = $entries;

        return $this;
    }

    private function clearColumns()
    {
        $this->columns = [];
    }

    private function appendPaginationLinks()
    {
        if (class_basename($this->entries) == 'LengthAwarePaginator') {
            // This set of models was paginated.  Make it append our current view variables.
            $this->entries->appends(Input::only(config('gbrock-tables.key_field'),
                config('gbrock-tables.key_direction')));
        }
    }

    /**
     * @return EntryCollection
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @return mixed
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setData($key, $value)
    {
        $this->data->put($key, $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setOptions(Collection $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setOption($key, $value)
    {
        $this->options->put($key, $value);

        return $this;
    }

    /**
     * @param        $key
     * @param  null  $default
     *
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return $this->options->get($key, $default);
    }

    public function addRow(Row $row)
    {
        $this->rows->push($row);

        return $this;
    }

    /**
     * Set the table rows.
     *
     * @param  RowCollection $rows
     *
     * @return $this
     */
    public function setRows(RowCollection $rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Get the table rows.
     *
     * @return RowCollection
     */
    public function getRows()
    {
        return $this->rows;
    }
}
