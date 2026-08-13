<?php

namespace Ghijk\CpNotifications\Tests\Pest\NudgeDeliveryRepositoryTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\EloquentNudgeDeliveryRepository;
use Ghijk\CpNotifications\Repositories\FileNudgeDeliveryRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

test('file delivery state updates the timestamp and count', function () {
    $directory = sys_get_temp_dir().'/cp-notifications-nudges-'.bin2hex(random_bytes(4));
    $repository = new FileNudgeDeliveryRepository(new Filesystem, $directory);

    $first = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 10:00'));
    $second = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 11:00'));

    expect($first->sendCount)->toBe(1);
    expect($second->sendCount)->toBe(2);
    expect($second->lastSentAt->equalTo('2026-08-12 11:00'))->toBeTrue();

    (new Filesystem)->deleteDirectory($directory);
});

test('eloquent delivery state updates the timestamp and count', function () {
    $migration = require __DIR__.'/../database/migrations/2026_08_12_000000_create_cp_notification_records_tables.php';
    $migration->up();
    $repository = new EloquentNudgeDeliveryRepository($this->app['db']->connection());

    $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 10:00'));
    $delivery = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 11:00'));

    expect($delivery->sendCount)->toBe(2);
    expect($delivery->lastSentAt->equalTo('2026-08-12 11:00'))->toBeTrue();

    $migration->down();
    expect(Schema::hasTable(EloquentNudgeDeliveryRepository::TABLE))->toBeFalse();
});
