<?php

namespace SuperV\Platform\Domains\Resource\Field\Jobs;

use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Field\Contracts\Field;

class GetRules
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $fields;

    public function __construct(Collection $fields)
    {
        $this->fields = $fields;
    }

    public function get(?EntryContract $entry = null, string $table = null)
    {
        return $this->fields
            ->filter(function (Field $field) {
                return ! $field->isUnbound();
            })
            ->keyBy(function (Field $field) {
                return $field->getColumnName();
            })
            ->map(function (Field $field) use ($table, $entry) {
                return (new ParseFieldRules($field))->parse($entry, $table);
            })
            ->filter()
            ->all();
    }
}