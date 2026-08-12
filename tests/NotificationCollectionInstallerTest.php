<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Console\Command;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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
        $this->assertSame('start_date', $collection->sortField());
        $this->assertSame('desc', $collection->sortDirection());
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
            ['title', 'notification_status', 'body', 'severity', 'blocking', 'snoozeable', 'priority', 'audience', 'start_date', 'end_date', 'nudge'],
            $fields->keys()->all(),
        );
        $this->assertSame('bard', $fields['body']->type());
        $this->assertSame(['info', 'warning', 'critical'], array_keys($fields['severity']->get('options')));
        $this->assertTrue($fields['start_date']->get('time_enabled'));
        $this->assertSame(
            ['title', 'notification_status', 'severity', 'blocking', 'start_date', 'end_date'],
            $blueprint->columns()->filter->visible()->pluck('field')->all(),
        );
        $this->assertSame('computed', $fields['notification_status']->visibility());
        $this->assertFalse($fields['body']->isListable());
        $this->assertTrue($fields['priority']->isListable());
        $this->assertFalse($fields['priority']->isVisibleOnListing());

        $audienceFields = collect($fields['audience']->get('fields'))->pluck('handle')->all();
        $nudgeFields = collect($fields['nudge']->get('fields'))->pluck('handle')->all();

        $this->assertSame(['all', 'roles', 'groups', 'users'], $audienceFields);
        $this->assertSame(['enabled', 'threshold_hours', 'cadence_hours'], $nudgeFields);
    }

    public function test_notifications_use_one_canonical_site_in_multisite_installs(): void
    {
        config()->set('statamic.system.multisite', true);
        Site::setSites([
            'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en_US'],
            'secondary' => ['name' => 'Secondary', 'url' => '/secondary/', 'locale' => 'en_US'],
        ]);

        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

        $collection = Collection::find('notifications');

        $this->assertSame(['default'], $collection->sites()->all());
        $this->assertFalse($collection->propagate());
        $this->assertNull($collection->route('default'));
        $this->assertFalse($collection->routes()->has('secondary'));
    }

    public function test_it_preserves_native_authorship_revisions_and_draft_lifecycle(): void
    {
        config()->set('statamic.revisions.enabled', true);
        $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

        $collection = Collection::find('notifications');
        $entry = Entry::make()
            ->collection($collection)
            ->published($collection->defaultPublishState())
            ->data(['author' => ['creator-id']]);

        $this->assertFalse($collection->defaultPublishState());
        $this->assertTrue($collection->fileData()['revisions']);
        $this->assertFalse($entry->published());
        $this->assertSame(['creator-id'], $entry->authors()->all());
    }
}
