<?php

namespace SuperV\Platform\Resources;

use SuperV\Platform\Domains\Resource\Hook\Contracts\ListConfigHook;
use SuperV\Platform\Domains\Resource\Resource\IndexFields;
use SuperV\Platform\Domains\Resource\Table\Contracts\TableInterface;

class TasksList implements ListConfigHook
{
    public static $identifier = 'platform.tasks.lists:default';

    public function config(TableInterface $table, IndexFields $fields)
    {
        $fields->show('status');
        $fields->show('created_at');

        $table->orderByLatest();
    }
}
