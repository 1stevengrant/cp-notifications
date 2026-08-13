<?php

namespace Ghijk\CpNotifications\Tests\Pest\NudgeCommandTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();
});

test('command queues only configured notices past their threshold', function () {
    Bus::fake();
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('eligible', ['enabled' => true, 'threshold_hours' => 24])->save();
    notice('disabled', ['enabled' => false, 'threshold_hours' => 24])->save();
    notice('too-early', ['enabled' => true, 'threshold_hours' => 49])->save();

    $this->artisan('cp-notifications:nudge')
        ->expectsOutputToContain('Queued reminders for 1 notification(s).')
        ->assertExitCode(Command::SUCCESS);

    Bus::assertDispatchedTimes(SendNotificationNudges::class, 1);
    Bus::assertDispatched(SendNotificationNudges::class, fn ($job): bool => $job->notificationId === 'eligible' && ! $job->manual
    );
});

function notice(string $id, array $nudge)
{
    return Entry::make()
        ->id($id)
        ->collection('notifications')
        ->locale(Site::default()->handle())
        ->data([
            'title' => ucfirst($id),
            'audience' => ['all' => true],
            'start_date' => '2026-08-10 12:00',
            'nudge' => $nudge,
        ]);
}
