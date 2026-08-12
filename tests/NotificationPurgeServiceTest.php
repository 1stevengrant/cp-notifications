<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Retention\NotificationPurgeService;
use Mockery;
use Psr\Log\LoggerInterface;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class NotificationPurgeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();
        parent::tearDown();
    }

    public function test_candidates_are_limited_to_published_notices_expired_past_the_retention_cutoff(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        config()->set('cp-notifications.retention.inbox_days', 30);
        CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('boundary', true, '2026-07-13 12:00')->save();
        $this->notice('older', true, '2026-07-12 12:00')->save();
        $this->notice('recent', true, '2026-07-13 12:00:01')->save();
        $this->notice('active', true, '2026-08-13 12:00')->save();
        $this->notice('open', true, null)->save();
        $this->notice('draft', false, '2026-07-01 12:00')->save();
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('forNotification')->andReturn(collect());
        $service = new NotificationPurgeService($acknowledgements, Mockery::mock(LoggerInterface::class));

        $this->assertEqualsCanonicalizing(
            ['boundary', 'older'],
            $service->candidates()->map->id()->all(),
        );
    }

    private function notice(string $id, bool $published, ?string $end)
    {
        return Entry::make()
            ->id($id)
            ->collection('notifications')
            ->locale(Site::default()->handle())
            ->published($published)
            ->data([
                'title' => ucfirst($id),
                'audience' => ['all' => true],
                'start_date' => '2026-01-01 00:00',
                'end_date' => $end,
            ]);
    }
}
