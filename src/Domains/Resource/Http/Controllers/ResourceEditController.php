<?php

namespace SuperV\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Resource\Http\ResolvesResource;
use SuperV\Platform\Domains\UI\Jobs\MakeComponentTree;
use SuperV\Platform\Domains\UI\Page\EntryPage;
use SuperV\Platform\Http\Controllers\BaseApiController;

class ResourceEditController extends BaseApiController
{
    use ResolvesResource;

    public function page()
    {
        $resource = $this->resolveResource();

        $page = EntryPage::make($resource->getEntryLabel($this->entry));
        $page->setResource($resource);
        $page->setEntry($this->entry);

        $page->addBlock(sv_loader($this->resource->route('edit', $this->entry)));

        if ($callback = $resource->getCallback('edit.page')) {
            app()->call($callback, ['page' => $page, 'entry' => $this->entry]);
        }

        return $page->build(['res' => $resource->toArray(), 'entry' => $this->entry]);
    }

    public function edit()
    {
        $resource = $this->resolveResource();

        return MakeComponentTree::dispatch($resource->resolveView($this->entry));
    }
}