<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationPurgeServiceTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Retention\NotificationPurgeService;
use Psr\Log\LoggerInterface;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->deleteQuietly();
    Collection::find('notifications')?->delete();
});

test('candidates are limited to published notices expired past the retention cutoff', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    config()->set('cp-notifications.retention.inbox_days', 30);
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('boundary', true, '2026-07-13 12:00')->save();
    notice('older', true, '2026-07-12 12:00')->save();
    notice('recent', true, '2026-07-13 12:00:01')->save();
    notice('active', true, '2026-08-13 12:00')->save();
    notice('open', true, null)->save();
    notice('draft', false, '2026-07-01 12:00')->save();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('forNotification')->andReturn(collect());
    $service = new NotificationPurgeService($acknowledgements, \Mockery::mock(LoggerInterface::class));

    expect($service->candidates()->map->id()->all())->toEqualCanonicalizing(['boundary', 'older']);
});

test('acknowledgement created after preview prevents deletion', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1', true, '2026-08-01 12:00')->save();
    $acknowledgement = new Acknowledgement(
        'ack-1',
        'notice-1',
        'user-1',
        CarbonImmutable::now(),
    );
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->expects('forNotification')->with('notice-1')->twice()->andReturn(
        collect(),
        collect([$acknowledgement]),
    );
    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->allows('info');
    $service = new NotificationPurgeService($acknowledgements, $logger);

    expect($service->purge('admin-1')->all())->toBe([]);
    expect(Entry::find('notice-1'))->not->toBeNull();
});

test('every successful purge logs actor ids timestamp and result', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1', true, '2026-08-01 12:00')->save();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('forNotification')->with('notice-1')->andReturn(collect());
    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->expects('info')->once()->with(
        'CP notification manual purge completed.',
        \Mockery::on(fn (array $context): bool => $context['actor_id'] === 'admin-1'
            && $context['notification_ids'] === ['notice-1']
            && $context['affected_count'] === 1
            && $context['occurred_at'] === '2026-08-12T12:00:00+12:00'
            && $context['result'] === 'success'
        ),
    );
    $service = new NotificationPurgeService($acknowledgements, $logger);

    expect($service->purge('admin-1')->all())->toBe(['notice-1']);
    expect(Entry::find('notice-1'))->toBeNull();
});

test('failed purge attempt is logged before the error is rethrown', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1', true, '2026-08-01 12:00')->save();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('forNotification')->andThrow(new \RuntimeException('storage unavailable'));
    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->expects('error')->once()->with(
        'CP notification manual purge failed.',
        \Mockery::on(fn (array $context): bool => $context['actor_id'] === 'admin-1'
            && $context['affected_count'] === 0
            && $context['result'] === 'failure'
            && $context['exception'] === \RuntimeException::class
        ),
    );

    $this->expectException(\RuntimeException::class);
    (new NotificationPurgeService($acknowledgements, $logger))->purge('admin-1');
});

function notice(string $id, bool $published, ?string $end)
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
