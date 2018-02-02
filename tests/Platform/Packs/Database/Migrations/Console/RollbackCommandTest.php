<?php

namespace Tests\SuperV\Platform\Packs\Database\Migrations\Console;

use SuperV\Platform\Packs\Database\Migrations\Console\RollbackCommand;
use SuperV\Platform\Packs\Database\Migrations\Migrator;
use Tests\SuperV\Platform\BaseTestCase;
use Mockery as m;
use Tests\SuperV\TestsConsoleCommands;

class RollbackCommandTest extends BaseTestCase
{
    use TestsConsoleCommands;

    /**
     * @test
     */
    function rollback_command_calls_migrator_with_proper_arguments()
    {
        $command = new RollbackCommand(
            $migrator = m::mock(Migrator::class)->shouldIgnoreMissing()
        );
        $command->setLaravel($this->app);

        $migrator->shouldReceive('setScope')->with('test-scope')->once();
        $migrator->shouldReceive('paths')->once()->andReturn([__DIR__.'/migrations']);
        $migrator->shouldReceive('rollback')->once();
        $migrator->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--scope' => 'test-scope']);
    }
}