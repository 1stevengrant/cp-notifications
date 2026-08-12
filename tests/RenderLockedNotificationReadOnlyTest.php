<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\RenderLockedNotificationReadOnly;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Mockery;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Fields\Blueprint;

class RenderLockedNotificationReadOnlyTest extends TestCase
{
    public function test_every_locked_notification_field_is_read_only_in_the_publish_form(): void
    {
        $blueprint = $this->blueprint();
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->with('notice-1')->andReturn(collect([
            new Acknowledgement(
                id: 'ack-1',
                notificationId: 'notice-1',
                userId: 'user-1',
                acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
            ),
        ]));

        (new RenderLockedNotificationReadOnly(new NotificationLock($repository)))
            ->handle(new EntryBlueprintFound($blueprint, $this->entry()));

        $this->assertSame(
            ['read_only', 'read_only'],
            $blueprint->fields()->all()->map->visibility()->values()->all(),
        );
    }

    public function test_unlocked_notification_blueprints_remain_editable(): void
    {
        $blueprint = $this->blueprint();
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->with('notice-1')->andReturn(collect());

        (new RenderLockedNotificationReadOnly(new NotificationLock($repository)))
            ->handle(new EntryBlueprintFound($blueprint, $this->entry()));

        $this->assertSame(
            ['visible', 'visible'],
            $blueprint->fields()->all()->map->visibility()->values()->all(),
        );
    }

    private function entry(): Entry
    {
        return (new Entry)
            ->id('notice-1')
            ->collection(Collection::make('notifications'));
    }

    private function blueprint(): Blueprint
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
}
