<?php

namespace Ghijk\CpNotifications\Console\Commands;

use Ghijk\CpNotifications\Content\NotificationCollectionInstaller;
use Illuminate\Console\Command;
use RuntimeException;

class InstallCommand extends Command
{
    protected $signature = 'cp-notifications:install';

    protected $description = 'Install the CP Notifications content model';

    public function handle(NotificationCollectionInstaller $installer): int
    {
        try {
            $installer->install();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('The notifications collection is ready.');

        return self::SUCCESS;
    }
}
