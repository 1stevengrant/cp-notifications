<?php

namespace Ghijk\CpNotifications\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'cp-notifications:install';

    protected $description = 'Install the CP Notifications content model';

    public function handle(): int
    {
        $this->components->info('CP Notifications is registered.');

        return self::SUCCESS;
    }
}
