<?php

namespace Ghijk\CpNotifications\Tests\Pest\RenderLockedNotificationReadOnlyTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\RenderLockedNotificationReadOnly;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Fields\Blueprint;

test('every locked notification field is read only in the publish form', function () {
    $blueprint = blueprint();
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('notice-1')->andReturn(collect([
        new Acknowledgement(
            id: 'ack-1',
            notificationId: 'notice-1',
            userId: 'user-1',
            acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
        ),
    ]));

    (new RenderLockedNotificationReadOnly(new NotificationLock($repository)))
        ->handle(new EntryBlueprintFound($blueprint, entry()));

    expect($blueprint->fields()->all()->map->visibility()->values()->all())->toBe(['read_only', 'read_only']);
});

test('unlocked notification blueprints remain editable', function () {
    $blueprint = blueprint();
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('notice-1')->andReturn(collect());

    (new RenderLockedNotificationReadOnly(new NotificationLock($repository)))
        ->handle(new EntryBlueprintFound($blueprint, entry()));

    expect($blueprint->fields()->all()->map->visibility()->values()->all())->toBe(['visible', 'visible']);
});

function entry(): Entry
{
    return (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'));
}

function blueprint(): Blueprint
{
    return Blueprint::make()->setContents([
        'tabs' => [
            'main' => [
                'sections' => [[
                    'fields' => [
                        ['handle' => 'title', 'field' => ['type' => 'text']],
                        ['handle' => 'body', 'field' => ['type' => 'textarea']],
                    ],
                ]],
            ],
        ],
    ]);
}
