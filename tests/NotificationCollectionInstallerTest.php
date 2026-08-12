<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Console\Command;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

class NotificationCollectionInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::find('notifications')?->delete();
        Blueprint::find('collections.notifications.notification')?->delete();
    }

    protected function tearDown(): void
    {
        Collection::find('notifications')?->delete();
        Blueprint::find('collections.notifications.notification')?->delete();

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

    public function test_it_creates_the_complete_notification_blueprint(): void
    {
        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

        $blueprint = Blueprint::find('collections.notifications.notification');

        $this->assertNotNull($blueprint);
        $this->assertSame('Notification', $blueprint->title());

        $fields = $blueprint->fields()->all();
        $this->assertSame(
            ['title', 'body', 'severity', 'blocking', 'snoozeable', 'priority', 'audience', 'start_date', 'end_date', 'nudge'],
            $fields->keys()->all(),
        );
        $this->assertSame('bard', $fields['body']->type());
        $this->assertSame(['info', 'warning', 'critical'], array_keys($fields['severity']->get('options')));
        $this->assertTrue($fields['start_date']->get('time_enabled'));

        $audienceFields = collect($fields['audience']->get('fields'))->pluck('handle')->all();
        $nudgeFields = collect($fields['nudge']->get('fields'))->pluck('handle')->all();

        $this->assertSame(['all', 'roles', 'groups', 'users'], $audienceFields);
        $this->assertSame(['enabled', 'threshold_hours', 'cadence_hours'], $nudgeFields);
    }
}
