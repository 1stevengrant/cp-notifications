<?php

namespace Ghijk\CpNotifications;

use Ghijk\CpNotifications\Console\Commands\InstallCommand;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        InstallCommand::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
            'resources/css/addon.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'cp-notifications-migrations');

        $this->registerNavigation();
    }

    public function registerNavigation(): void
    {
        Nav::extend(function (Navigation $nav): void {
            $nav->create('Inbox')
                ->section('Notifications')
                ->icon('inbox')
                ->route('cp-notifications.inbox')
                ->can('view notifications');

            $nav->create('Manage')
                ->section('Notifications')
                ->icon('collection')
                ->route('cp-notifications.manage')
                ->can('manage notifications');

            $nav->create('Reports')
                ->section('Notifications')
                ->icon('charts')
                ->route('cp-notifications.reports')
                ->can('view notification reports');
        });
    }
}
