<?php

use SuperV\Platform\Domains\Auth\Console\CreateUserCommand;
use SuperV\Platform\Domains\Database\Migration\Console\DropletMigrationCommand;
use SuperV\Platform\Domains\Droplet\Console\DropletReinstallCommand;
use SuperV\Platform\Domains\Droplet\Console\DropletUninstallCommand;
use SuperV\Platform\Domains\Droplet\Console\MakeDropletCommand;

return [
    DropletUninstallCommand::class,
    DropletReinstallCommand::class,
    MakeDropletCommand::class,
    CreateUserCommand::class,
];