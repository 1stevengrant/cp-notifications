<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class NudgeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();
        parent::tearDown();
    }

    public function test_command_queues_only_configured_notices_past_their_threshold(): void
    {
        Bus::fake();
        config()->set('app.timezone', 'Pacific/Auckland');
        CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('eligible', ['enabled' => true, 'threshold_hours' => 24])->save();
        $this->notice('disabled', ['enabled' => false, 'threshold_hours' => 24])->save();
        $this->notice('too-early', ['enabled' => true, 'threshold_hours' => 49])->save();

        $this->artisan('cp-notifications:nudge')
            ->expectsOutputToContain('Queued reminders for 1 notification(s).')
            ->assertExitCode(Command::SUCCESS);

        Bus::assertDispatchedTimes(SendNotificationNudges::class, 1);
        Bus::assertDispatched(SendNotificationNudges::class, fn ($job): bool =>
            $job->notificationId === 'eligible' && ! $job->manual
        );
    }

    private function notice(string $id, array $nudge)
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
}
