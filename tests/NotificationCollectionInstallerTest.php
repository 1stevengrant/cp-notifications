<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Console\Command;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

class NotificationCollectionInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::find('notifications')?->delete();
    }

    protected function tearDown(): void
    {
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_it_creates_a_routeless_cp_only_notifications_collection(): void
    {
        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

        $collection = Collection::find('notifications');

        $this->assertNotNull($collection);
        $this->assertSame('Notifications', $collection->title());
        $this->assertNull($collection->route(Site::default()->handle()));
        $this->assertFalse($collection->requiresSlugs());
    }

    public function test_installing_the_collection_is_idempotent(): void
    {
        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);
        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

        $this->assertSame(
            1,
            Collection::all()->where('handle', 'notifications')->count(),
        );
    }

    public function test_it_refuses_to_take_over_an_existing_routed_collection(): void
    {
        Collection::make('notifications')->routes('/news/{slug}')->save();

        $this->artisan('cp-notifications:install')
            ->expectsOutputToContain('existing notifications collection is routed')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(
            '/news/{slug}',
            Collection::find('notifications')->route(Site::default()->handle()),
        );
    }
}
