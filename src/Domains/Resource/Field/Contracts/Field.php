<?php

namespace SuperV\Platform\Domains\Resource\Field\Contracts;

use Closure;
use Illuminate\Http\Request;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Database\Model\Contracts\Watcher;

interface Field
{
    public function getName();

    public function getColumnName();

    public function getLabel(): string;

    public function setLabel(string $label): Field;

    public function getType();

    public function getConfig();

    public function getConfigValue($key, $default = null);

    public function setConfigValue($key, $value = null);

    public function setWatcher(Watcher $watcher);

    public function isHidden();

    public function isUnique();

    public function isRequired();

    public function doesNotInteractWithTable();

    public function hide();

    public function getAlterQueryCallback();

    public function getRules();

    public function fillFromEntry(EntryContract $entry);

    public function resolveRequestToEntry(Request $request, EntryContract $entry);

    public function resolveFromEntry(EntryContract $entry);

    public function getAccessor();

    public function getComposer();

    public function getPresenter();

    public function setPresenter(Closure $callback);
}