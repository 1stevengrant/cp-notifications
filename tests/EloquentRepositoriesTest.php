<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentRepositoriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(EloquentAcknowledgementRepository::TABLE, function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('notification_id');
            $table->string('user_id');
            $table->dateTimeTz('acknowledged_at');
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::create(EloquentSnoozeRepository::TABLE, function (Blueprint $table): void {
            $table->string('notification_id');
            $table->string('user_id');
            $table->dateTimeTz('snoozed_until');
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function test_acknowledgements_are_idempotent_and_queryable(): void
    {
        $repository = new EloquentAcknowledgementRepository(DB::connection());
        $firstTime = CarbonImmutable::parse('2026-08-12T10:00:00+12:00');
        $secondTime = $firstTime->addHour();

        $first = $repository->record('notice-1', 'user-1', $firstTime);
        $duplicate = $repository->record('notice-1', 'user-1', $secondTime);
        $repository->record('notice-1', 'user-2', $secondTime);

        $this->assertSame($first->id, $duplicate->id);
        $this->assertTrue($duplicate->acknowledgedAt->equalTo($firstTime));
        $this->assertSame($first->id, $repository->find('notice-1', 'user-1')?->id);
        $this->assertCount(2, $repository->forNotification('notice-1'));
        $this->assertCount(1, $repository->forUser('user-1'));
        $this->assertNull($repository->find('missing', 'user-1'));
    }

    public function test_snoozes_are_single_use_and_queryable(): void
    {
        $repository = new EloquentSnoozeRepository(DB::connection());
        $firstExpiry = CarbonImmutable::parse('2026-08-13T10:00:00+12:00');
        $secondExpiry = $firstExpiry->addDay();

        $first = $repository->record('notice-1', 'user-1', $firstExpiry);
        $duplicate = $repository->record('notice-1', 'user-1', $secondExpiry);
        $repository->record('notice-1', 'user-2', $secondExpiry);

        $this->assertTrue($first->snoozedUntil->equalTo($duplicate->snoozedUntil));
        $this->assertTrue($duplicate->snoozedUntil->equalTo($firstExpiry));
        $this->assertNotNull($repository->find('notice-1', 'user-1'));
        $this->assertCount(2, $repository->forNotification('notice-1'));
        $this->assertCount(1, $repository->forUser('user-1'));
        $this->assertNull($repository->find('missing', 'user-1'));
    }

    public function test_snoozes_default_to_exactly_twenty_four_hours(): void
    {
        CarbonImmutable::setTestNow('2026-08-12T10:00:00+12:00');

        try {
            $snooze = (new EloquentSnoozeRepository(DB::connection()))->record('notice-1', 'user-1');

            $this->assertTrue($snooze->snoozedUntil->equalTo(CarbonImmutable::now()->addDay()));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
