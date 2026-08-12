<?php

namespace Ghijk\CpNotifications;

use Ghijk\CpNotifications\Console\Commands\InstallCommand;
use Ghijk\CpNotifications\Console\Commands\NudgeCommand;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Contracts\NudgeDeliveryRepository;
use Ghijk\CpNotifications\Listeners\ValidateNotificationAudience;
use Ghijk\CpNotifications\Listeners\NormalizeNotificationBehavior;
use Ghijk\CpNotifications\Listeners\PreventLockedNotificationEdits;
use Ghijk\CpNotifications\Listeners\RenderLockedNotificationReadOnly;
use Ghijk\CpNotifications\Http\Middleware\EnforceBlockingNotifications;
use Ghijk\CpNotifications\Notifications\NotificationStatus;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileNudgeDeliveryRepository;
use Ghijk\CpNotifications\Repositories\EloquentNudgeDeliveryRepository;
use Ghijk\CpNotifications\Repositories\RepositoryDriverResolver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Events\EntrySaving;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Collection;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'statamic.cp.authenticated' => [
            EnforceBlockingNotifications::class,
        ],
    ];

    protected $listen = [
        EntryBlueprintFound::class => [
            RenderLockedNotificationReadOnly::class,
        ],
        EntrySaving::class => [
            PreventLockedNotificationEdits::class,
            NormalizeNotificationBehavior::class,
            ValidateNotificationAudience::class,
        ],
    ];

    protected $commands = [
        InstallCommand::class,
        NudgeCommand::class,
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

    public function register(): void
    {
        $this->app->singleton(RepositoryDriverResolver::class);

        $this->app->singleton(AcknowledgementRepository::class, function (Application $app) {
            return $this->repositoryDriver($app) === 'eloquent'
                ? new EloquentAcknowledgementRepository($app->make('db')->connection())
                : new FileAcknowledgementRepository($app->make('files'), $this->repositoryStoragePath($app));
        });

        $this->app->singleton(SnoozeRepository::class, function (Application $app) {
            return $this->repositoryDriver($app) === 'eloquent'
                ? new EloquentSnoozeRepository($app->make('db')->connection())
                : new FileSnoozeRepository($app->make('files'), $this->repositoryStoragePath($app));
        });

        $this->app->singleton(NudgeDeliveryRepository::class, function (Application $app) {
            return $this->repositoryDriver($app) === 'eloquent'
                ? new EloquentNudgeDeliveryRepository($app->make('db')->connection())
                : new FileNudgeDeliveryRepository($app->make('files'), $this->repositoryStoragePath($app));
        });
    }

    public function bootAddon(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'cp-notifications-migrations');

        $this->registerNavigation();
        $this->registerPermissions();
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('cp-notifications:nudge')
                ->hourly()
                ->withoutOverlapping();
        });

        Collection::computed(
            'notifications',
            'notification_status',
            static fn ($entry): string => app(NotificationStatus::class)->for($entry),
        );
    }

    public function registerNavigation(): void
    {
        Nav::extend(function (Navigation $nav): void {
            $nav->create('Inbox')
                ->section('Notifications')
                ->icon('inbox')
                ->route('cp-notifications.inbox');

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

    public function registerPermissions(): void
    {
        Permission::extend(function (): void {
            Permission::group('cp-notifications', 'CP Notifications', function (): void {
                Permission::register('view notifications')
                    ->label('View own notification inbox');
                Permission::register('manage notifications')
                    ->label('Manage notifications');
                Permission::register('view notification reports')
                    ->label('View notification reports');
                Permission::register('bypass notifications')
                    ->label('Bypass notification enforcement');
                Permission::register('purge notifications')
                    ->label('Purge expired notifications');
            });
        });
    }

    private function repositoryDriver(Application $app): string
    {
        return $app->make(RepositoryDriverResolver::class)->resolve(
            $app->make('config')->get('cp-notifications.acknowledgements.driver', 'auto'),
        );
    }

    private function repositoryStoragePath(Application $app): string
    {
        return $app->make('config')->get(
            'cp-notifications.acknowledgements.file_path',
            storage_path('statamic/cp-notifications'),
        );
    }
}
