<?php

namespace SuperV\Platform\Domains\Resource;

use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Extension\Extension;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Field\FieldModel;
use SuperV\Platform\Domains\Resource\Relation\Relation;
use SuperV\Platform\Domains\Resource\Relation\RelationFactory;
use SuperV\Platform\Domains\Resource\Relation\RelationModel;
use SuperV\Platform\Domains\Resource\Resource\EntryResource;
use SuperV\Platform\Exceptions\PlatformException;

class ResourceFactory
{
    /**
     * @var string
     */
    protected $handle;

    /**
     * @var \SuperV\Platform\Domains\Resource\ResourceModel
     */
    protected $model;

    /**
     * @var \SuperV\Platform\Domains\Database\Model\Contracts\EntryContract|null
     */
    protected $entry;

    protected function __construct(string $handle, ?EntryContract $entry = null)
    {
        $this->handle = $handle;
        $this->entry = $entry;
    }

    protected function getFieldsProvider()
    {
        return function () {
            $fields = $this->model->getFields()
                                  ->map(function (FieldModel $fieldEntry) {
                                      $field = FieldFactory::createFromEntry($fieldEntry);
                                      if ($this->entry) {
                                          $field->setEntry($this->entry);
                                      }

                                      return $field;
                                  });

            return $fields ?? collect();
        };
    }

    protected function getRelationsProvider()
    {
        return function () {
            return $this->model->getResourceRelations()
                               ->map(function (RelationModel $relation) {
                                   $relation = (new RelationFactory)->make($relation);

                                   return $relation;
                               })
                               ->keyBy(function (Relation $relation) { return $relation->getName(); });
        };
    }

    protected function get()
    {
        if (! $this->model = ResourceModel::withHandle($this->handle)) {
            throw new PlatformException("Resource model entry not found for [{$this->handle}]");
        }

        $attributes = array_merge($this->model->toArray(), [
            'handle'    => $this->model->getHandle(),
            'fields'    => $this->getFieldsProvider(),
            'relations' => $this->getRelationsProvider(),
        ]);

        return $attributes;
    }

    public static function attributesFor(string $handle, ?EntryContract $entry = null): array
    {
        return (new static($handle, $entry))->get();
    }

    /** @return \SuperV\Platform\Domains\Resource\Resource */
    public static function make(string $handle)
    {
        $resource = new Resource(static::attributesFor($handle));

        Extension::extend($resource);

        return $resource;
    }

    public static function makeWithEntry(EntryContract $entry)
    {
        $resource = new EntryResource($entry, static::attributesFor($entry->getTable(), $entry));

        Extension::extend($resource);

        return $resource;
    }
}