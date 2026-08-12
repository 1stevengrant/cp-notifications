<?php

namespace Ghijk\CpNotifications\Tests\Pest\EloquentRepositoriesTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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
});

test('acknowledgements are idempotent and queryable', function () {
    $repository = new EloquentAcknowledgementRepository(DB::connection());
    $firstTime = CarbonImmutable::parse('2026-08-12T10:00:00+12:00');
    $secondTime = $firstTime->addHour();

    $first = $repository->record('notice-1', 'user-1', $firstTime);
    $duplicate = $repository->record('notice-1', 'user-1', $secondTime);
    $repository->record('notice-1', 'user-2', $secondTime);

    expect($duplicate->id)->toBe($first->id);
    expect($duplicate->acknowledgedAt->equalTo($firstTime))->toBeTrue();
    expect($repository->find('notice-1', 'user-1')?->id)->toBe($first->id);
    expect($repository->forNotification('notice-1'))->toHaveCount(2);
    expect($repository->forUser('user-1'))->toHaveCount(1);
    expect($repository->find('missing', 'user-1'))->toBeNull();
});

test('snoozes are single use and queryable', function () {
    $repository = new EloquentSnoozeRepository(DB::connection());
    $firstExpiry = CarbonImmutable::parse('2026-08-13T10:00:00+12:00');
    $secondExpiry = $firstExpiry->addDay();

    $first = $repository->record('notice-1', 'user-1', $firstExpiry);
    $duplicate = $repository->record('notice-1', 'user-1', $secondExpiry);
    $repository->record('notice-1', 'user-2', $secondExpiry);

    expect($first->snoozedUntil->equalTo($duplicate->snoozedUntil))->toBeTrue();
    expect($duplicate->snoozedUntil->equalTo($firstExpiry))->toBeTrue();
    expect($repository->find('notice-1', 'user-1'))->not->toBeNull();
    expect($repository->forNotification('notice-1'))->toHaveCount(2);
    expect($repository->forUser('user-1'))->toHaveCount(1);
    expect($repository->find('missing', 'user-1'))->toBeNull();
});

test('snoozes default to exactly twenty four hours', function () {
    CarbonImmutable::setTestNow('2026-08-12T10:00:00+12:00');

    try {
        $snooze = (new EloquentSnoozeRepository(DB::connection()))->record('notice-1', 'user-1');

        expect($snooze->snoozedUntil->equalTo(CarbonImmutable::now()->addDay()))->toBeTrue();
    } finally {
        CarbonImmutable::setTestNow();
    }
});
