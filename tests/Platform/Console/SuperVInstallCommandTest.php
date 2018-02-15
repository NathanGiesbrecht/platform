<?php

namespace Tests\SuperV\Platform\Console;

use SuperV\Platform\Domains\Droplet\DropletModel;
use Tests\SuperV\Platform\BaseTestCase;
use Tests\SuperV\TestsConsoleCommands;

class SuperVInstallCommandTest extends BaseTestCase
{
    use TestsConsoleCommands;

    /** @test */
    function sets_env_variable_to_installed()
    {
        $this->app->setBasePath(base_path('tests'));
        file_put_contents(base_path('.env'), 'SV_INSTALLED=false');

        $this->artisan('superv:install');

        $this->assertContains('SV_INSTALLED=true', file_get_contents(base_path('.env')));
    }

    /** @test */
    function runs_proper_migrations()
    {
        $this->artisan('superv:install');

        $this->assertEquals(0, DropletModel::count());
    }

}