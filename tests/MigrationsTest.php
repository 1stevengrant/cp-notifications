<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    public function test_record_tables_have_their_storage_and_lookup_columns(): void
    {
        $migration = $this->recordTablesMigration();
        $migration->up();

        $this->assertTrue(Schema::hasColumns(EloquentAcknowledgementRepository::TABLE, [
            'id',
            'notification_id',
            'user_id',
            'acknowledged_at',
        ]));
        $this->assertTrue(Schema::hasColumns(EloquentSnoozeRepository::TABLE, [
            'notification_id',
            'user_id',
            'snoozed_until',
        ]));

        $ackIndexes = collect(Schema::getIndexes(EloquentAcknowledgementRepository::TABLE))->pluck('name');
        $snoozeIndexes = collect(Schema::getIndexes(EloquentSnoozeRepository::TABLE))->pluck('name');

        $this->assertContains('cp_notification_acknowledgements_notification_id_index', $ackIndexes);
        $this->assertContains('cp_notification_acknowledgements_user_id_index', $ackIndexes);
        $this->assertContains('cp_notification_acknowledgements_acknowledged_at_index', $ackIndexes);
        $this->assertContains('cp_notification_snoozes_notification_id_index', $snoozeIndexes);
        $this->assertContains('cp_notification_snoozes_user_id_index', $snoozeIndexes);
        $this->assertContains('cp_notification_snoozes_snoozed_until_index', $snoozeIndexes);

        $this->assertTrue(collect(Schema::getIndexes(EloquentAcknowledgementRepository::TABLE))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['notification_id', 'user_id'],
        ));
        $this->assertTrue(collect(Schema::getIndexes(EloquentSnoozeRepository::TABLE))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['notification_id', 'user_id'],
        ));

        $migration->down();

        $this->assertFalse(Schema::hasTable(EloquentAcknowledgementRepository::TABLE));
        $this->assertFalse(Schema::hasTable(EloquentSnoozeRepository::TABLE));
    }

    public function test_acknowledgements_are_unique_per_notification_and_user(): void
    {
        $this->recordTablesMigration()->up();
        $record = [
            'id' => 'ack-1',
            'notification_id' => 'notice-1',
            'user_id' => 'user-1',
            'acknowledged_at' => '2026-08-12T10:00:00+12:00',
        ];

        DB::table(EloquentAcknowledgementRepository::TABLE)->insert($record);

        $this->expectException(QueryException::class);
        DB::table(EloquentAcknowledgementRepository::TABLE)->insert([...$record, 'id' => 'ack-2']);
    }

    public function test_snoozes_are_unique_per_notification_and_user(): void
    {
        $this->recordTablesMigration()->up();
        $record = [
            'notification_id' => 'notice-1',
            'user_id' => 'user-1',
            'snoozed_until' => '2026-08-13T10:00:00+12:00',
        ];

        DB::table(EloquentSnoozeRepository::TABLE)->insert($record);

        $this->expectException(QueryException::class);
        DB::table(EloquentSnoozeRepository::TABLE)->insert($record);
    }

    private function recordTablesMigration(): Migration
    {
        return require __DIR__.'/../database/migrations/2026_08_12_000000_create_cp_notification_records_tables.php';
    }
}
