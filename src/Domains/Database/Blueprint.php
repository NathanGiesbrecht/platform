<?php

namespace SuperV\Platform\Domains\Database;

use Closure;
use Current;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;
use SuperV\Platform\Domains\Database\Events\ColumnCreatedEvent;
use SuperV\Platform\Domains\Database\Events\ColumnDroppedEvent;
use SuperV\Platform\Domains\Database\Events\ColumnUpdatedEvent;
use SuperV\Platform\Domains\Database\Events\TableCreatedEvent;
use SuperV\Platform\Domains\Database\Events\TableCreatingEvent;
use SuperV\Platform\Domains\Database\Events\TableDroppedEvent;
use SuperV\Platform\Domains\Resource\Relation\RelationConfig as Config;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * @var \SuperV\Platform\Domains\Database\Schema
     */
    protected $builder;

    public function __construct(string $table, ?\Closure $callback = null, Schema $builder = null)
    {
        parent::__construct($table, $callback);

        $this->builder = $builder;
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  string $type
     * @param  string $name
     * @param  array  $parameters
     * @return \SuperV\Platform\Domains\Database\ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        $this->columns[] = $column = new ColumnDefinition(
            $this->builder ? $this->builder->resource() : new \SuperV\Platform\Domains\Resource\ResourceBlueprint,
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    public function build(Connection $connection, Grammar $grammar)
    {
        if ($this->dropping()) {
            if (! $this->builder->justRun) {
                parent::build($connection, $grammar);
            }

            TableDroppedEvent::dispatch($this->tableName());

            return;
        }

        if ($this->creating()) {
            TableCreatingEvent::dispatch($this->tableName(), $this->columns, $this->builder->resource(), Current::migrationScope());
        } else {
            // Dropping Columns
            foreach ($this->commands as $command) {
                if ($command->name === 'dropColumn') {
                    sv_collect($command->columns)->map(function ($column) {
                        ColumnDroppedEvent::dispatch($this->tableName(), $column);
                    });
                }
            }
        }

        sv_collect($this->getChangedColumns())->map(function (Fluent $column) {
            ColumnUpdatedEvent::dispatch($this->tableName(), $column);
        });

        sv_collect($this->getAddedColumns())->map(function ($column) {
            ColumnCreatedEvent::dispatch($this->tableName(), $column, $this->builder->resource()->model);
        });

        $this->columns = array_filter($this->columns, function ($column) {
            return ! $column->ignore;
        });

        if (! $this->builder->justRun) {
            parent::build($connection, $grammar);
        }

        if ($this->creating()) {
            TableCreatedEvent::dispatch($this->tableName(), $this->columns);
        }
    }

    /**
     * Determine if the blueprint has a drop or dropIfExists command.
     *
     * @return bool
     */
    protected function dropping()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name == 'drop' || $command->name == 'dropIfExists';
        });
    }

    public function email($name)
    {
        return $this->string($name)->fieldType('email');
    }

    public function file($name)
    {
        return $this->addColumn(null, $name)->fieldType('file')->ignore();
    }

    public function getColumnNames(): array
    {
        return sv_collect($this->getColumns())->pluck('name')->all();
    }

    public function nullableBelongsTo($related, $relation, $foreignKey = null, $ownerKey = null)
    {
        return $this->belongsTo($related, $relation, $foreignKey, $ownerKey)->nullable();
    }

    public function belongsTo($related, $relationName, $foreignKey = null, $ownerKey = null)
    {
        $this->addColumn(null, $relationName, ['nullable' => true])
             ->relation(
                 Config::belongsTo()
                       ->relationName($relationName)
                       ->related($related)
                       ->foreignKey($foreignKey ?? $relationName.'_id')
                       ->ownerKey($ownerKey)
             );

        return $this->unsignedInteger($foreignKey ?? $relationName.'_id')
                    ->fieldType('belongs_to')
                    ->fieldName($relationName)
                    ->config(
                        Config::belongsTo()
                              ->relationName($relationName)
                              ->related($related)
                              ->foreignKey($foreignKey ?? $relationName.'_id')
                              ->ownerKey($ownerKey)
                              ->toArray()
                    );
    }

    public function belongsToMany(
        $related,
        $relationName,
        $pivotTable = null,
        $pivotForeignKey = null,
        $pivotRelatedKey = null,
        Closure $pivotColumns = null
    ) {
        return $this->addColumn(null, $relationName, ['nullable' => true])
                    ->relation(
                        Config::belongsToMany()
                              ->relationName($relationName)
                              ->related($related)
                              ->pivotTable($pivotTable)
                              ->pivotForeignKey($pivotForeignKey)
                              ->pivotRelatedKey($pivotRelatedKey)
                              ->pivotColumns($pivotColumns)
                    );
    }

    public function hasOne($related, $relationName, $foreignKey, $localKey = null)
    {
        return $this->addColumn(null, $relationName, ['nullable' => true])
                    ->relation(
                        Config::hasOne()
                              ->relationName($relationName)
                              ->related($related)
                              ->foreignKey($foreignKey)
                              ->localKey($localKey)
                    );
    }

    public function hasMany($related, $relationName, $foreignKey, $localKey = null)
    {
        return $this->addColumn(null, $relationName, ['nullable' => true])
                    ->relation(
                        Config::hasMany()
                              ->relationName($relationName)
                              ->related($related)
                              ->foreignKey($foreignKey)
                              ->localKey($localKey)
                    );
    }

    public function resourceeeeeeeeee()
    {
        return $this->builder->resource();
    }

    public function morphToMany(
        $related,
        $relationName,
        $morphName,
        $pivotTable = null,
        $pivotRelatedKey = null,
        Closure $pivotColumns = null
    ) {
        return $this->addColumn(null, $relationName, ['nullable' => true])
                    ->relation(
                        Config::morphToMany()
                              ->relationName($relationName)
                              ->related($related)
                              ->pivotTable($pivotTable)
                              ->pivotForeignKey($morphName.'_id')
                              ->pivotRelatedKey($pivotRelatedKey)
                              ->pivotColumns($pivotColumns)
                              ->morphName($morphName)
                    );
    }

    public function morphOne(
        $related,
        $relationName,
        $morphName
    ) {
        return $this->addColumn(null, $relationName, ['nullable' => true])
                    ->relation(
                        Config::morphOne()
                              ->relationName($relationName)
                              ->related($related)
                              ->morphName($morphName)
                    );
    }

    public function select($name): ColumnDefinition
    {
        return $this->string($name)->fieldType('select');
    }

    public function tableName()
    {
        return $this->table;
    }
}