<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\EloquentNudgeDeliveryRepository;
use Ghijk\CpNotifications\Repositories\FileNudgeDeliveryRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

class NudgeDeliveryRepositoryTest extends TestCase
{
    public function test_file_delivery_state_updates_the_timestamp_and_count(): void
    {
        $directory = sys_get_temp_dir().'/cp-notifications-nudges-'.bin2hex(random_bytes(4));
        $repository = new FileNudgeDeliveryRepository(new Filesystem, $directory);

        $first = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 10:00'));
        $second = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 11:00'));

        $this->assertSame(1, $first->sendCount);
        $this->assertSame(2, $second->sendCount);
        $this->assertTrue($second->lastSentAt->equalTo('2026-08-12 11:00'));

        (new Filesystem)->deleteDirectory($directory);
    }

    public function test_eloquent_delivery_state_updates_the_timestamp_and_count(): void
    {
        $migration = require __DIR__.'/../database/migrations/2026_08_12_000000_create_cp_notification_records_tables.php';
        $migration->up();
        $repository = new EloquentNudgeDeliveryRepository($this->app['db']->connection());

        $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 10:00'));
        $delivery = $repository->recordSent('notice-1', 'user-1', CarbonImmutable::parse('2026-08-12 11:00'));

        $this->assertSame(2, $delivery->sendCount);
        $this->assertTrue($delivery->lastSentAt->equalTo('2026-08-12 11:00'));

        $migration->down();
        $this->assertFalse(Schema::hasTable(EloquentNudgeDeliveryRepository::TABLE));
    }
}
