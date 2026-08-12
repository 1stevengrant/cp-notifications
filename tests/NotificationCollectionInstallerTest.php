<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationCollectionInstallerTest;

use Illuminate\Console\Command;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

beforeEach(function () {
    Collection::find('notifications')?->delete();
    Blueprint::find('collections.notifications.notification')?->delete();
});

afterEach(function () {
    Collection::find('notifications')?->delete();
    Blueprint::find('collections.notifications.notification')?->delete();

});

test('it creates a routeless cp only notifications collection', function () {
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    $collection = Collection::find('notifications');

    expect($collection)->not->toBeNull();
    expect($collection->title())->toBe('Notifications');
    expect($collection->route(Site::default()->handle()))->toBeNull();
    expect($collection->routes()->filter()->all())->toBe([]);
    expect($collection->requiresSlugs())->toBeFalse();
    expect($collection->sortField())->toBe('start_date');
    expect($collection->sortDirection())->toBe('desc');
});

test('installing the collection is idempotent', function () {
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    expect(Collection::all()->where('handle', 'notifications')->count())->toBe(1);
});

test('it refuses to take over an existing routed collection', function () {
    Collection::make('notifications')->routes('/news/{slug}')->save();

    $this->artisan('cp-notifications:install')
        ->expectsOutputToContain('existing notifications collection is routed')
        ->assertExitCode(Command::FAILURE);

    expect(Collection::find('notifications')->route(Site::default()->handle()))->toBe('/news/{slug}');
});

test('it creates the complete notification blueprint', function () {
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    $blueprint = Blueprint::find('collections.notifications.notification');

    expect($blueprint)->not->toBeNull();
    expect($blueprint->title())->toBe('Notification');

    $fields = $blueprint->fields()->all();
    expect($fields->keys()->all())->toBe(['title', 'notification_status', 'body', 'severity', 'blocking', 'snoozeable', 'priority', 'audience', 'start_date', 'end_date', 'nudge']);
    expect($fields['body']->type())->toBe('bard');
    expect(array_keys($fields['severity']->get('options')))->toBe(['info', 'warning', 'critical']);
    expect($fields['start_date']->get('time_enabled'))->toBeTrue();
    expect($blueprint->columns()->filter->visible()->pluck('field')->all())->toBe(['title', 'notification_status', 'severity', 'blocking', 'start_date', 'end_date']);
    expect($fields['notification_status']->visibility())->toBe('computed');
    expect($fields['body']->isListable())->toBeFalse();
    expect($fields['priority']->isListable())->toBeTrue();
    expect($fields['priority']->isVisibleOnListing())->toBeFalse();

    $audienceFields = collect($fields['audience']->get('fields'))->pluck('handle')->all();
    $nudgeFields = collect($fields['nudge']->get('fields'))->pluck('handle')->all();

    expect($audienceFields)->toBe(['all', 'roles', 'groups', 'users']);
    expect($nudgeFields)->toBe(['enabled', 'threshold_hours', 'cadence_hours']);
});

test('every notification field explains its publishing behavior', function () {
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    $fields = Blueprint::find('collections.notifications.notification')->fields()->all();

    foreach ($fields as $handle => $field) {
        expect($field->get('instructions'), "Missing instructions for {$handle}")->toBeString()->not->toBeEmpty();
    }

    foreach (['audience', 'nudge'] as $groupHandle) {
        foreach ($fields[$groupHandle]->get('fields') as $field) {
            expect($field['field']['instructions'], "Missing instructions for {$groupHandle}.{$field['handle']}")
                ->toBeString()
                ->not->toBeEmpty();
        }
    }

    expect($fields['severity']->get('instructions'))->toContain('does not make a notice blocking');
    expect($fields['blocking']->get('instructions'))->toContain('cannot be snoozed');
    expect($fields['end_date']->get('instructions'))->toContain('stops blocking');
    expect($fields['nudge']->get('instructions'))->toContain('currently targeted users');
});

test('notifications use one canonical site in multisite installs', function () {
    config()->set('statamic.system.multisite', true);
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en_US'],
        'secondary' => ['name' => 'Secondary', 'url' => '/secondary/', 'locale' => 'en_US'],
    ]);

    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    $collection = Collection::find('notifications');

    expect($collection->sites()->all())->toBe(['default']);
    expect($collection->propagate())->toBeFalse();
    expect($collection->route('default'))->toBeNull();
    expect($collection->routes()->has('secondary'))->toBeFalse();
});

test('it preserves native authorship revisions and draft lifecycle', function () {
    config()->set('statamic.revisions.enabled', true);
    $this->artisan('cp-notifications:install')->assertExitCode(Command::SUCCESS);

    $collection = Collection::find('notifications');
    $entry = Entry::make()
        ->collection($collection)
        ->published($collection->defaultPublishState())
        ->data(['author' => ['creator-id']]);

    expect($collection->defaultPublishState())->toBeFalse();
    expect($collection->fileData()['revisions'])->toBeTrue();
    expect($entry->published())->toBeFalse();
    expect($entry->authors()->all())->toBe(['creator-id']);
});
