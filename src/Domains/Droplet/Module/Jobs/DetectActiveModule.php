<?php

namespace SuperV\Platform\Domains\Droplet\Module\Jobs;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\View\Factory;
use SuperV\Platform\Domains\Droplet\Model\DropletCollection;
use SuperV\Platform\Domains\Droplet\Model\Droplets;
use SuperV\Platform\Domains\Droplet\Module\Module;
use SuperV\Platform\Domains\UI\Navigation\Navigation;

class DetectActiveModule
{
    /**
     * @var Droplets
     */
    private $droplets;

    /**
     * @var Navigation
     */
    private $navigation;

    /**
     * @var Factory
     */
    private $view;

    public function __construct(DropletCollection $droplets, Navigation $navigation, Factory $view)
    {
        $this->droplets = $droplets;
        $this->navigation = $navigation;
        $this->view = $view;
    }

    public function handle(RouteMatched $event)
    {
        /** @var Route $route */
        if (! $route = $event->route) {
            return;
        }

        if (! $slug = array_get($route->getAction(), 'superv::droplet')) {
            return;
        }

        /** @var Module $module */
        $module = $this->droplets->get($slug);

        $this->view->addNamespace('module', [base_path($module->getPath('resources/views'))]);

        $this->navigation->setActiveModule($module);
    }
}