<?php

namespace Ghijk\CpNotifications\Tests\Pest\MigrationsTest;

use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('record tables have their storage and lookup columns', function () {
    $migration = recordTablesMigration();
    $migration->up();

    expect(Schema::hasColumns(EloquentAcknowledgementRepository::TABLE, [
        'id',
        'notification_id',
        'user_id',
        'acknowledged_at',
    ]))->toBeTrue();
    expect(Schema::hasColumns(EloquentSnoozeRepository::TABLE, [
        'notification_id',
        'user_id',
        'snoozed_until',
    ]))->toBeTrue();

    $ackIndexes = collect(Schema::getIndexes(EloquentAcknowledgementRepository::TABLE))->pluck('name');
    $snoozeIndexes = collect(Schema::getIndexes(EloquentSnoozeRepository::TABLE))->pluck('name');

    expect($ackIndexes)->toContain('cp_notification_acknowledgements_notification_id_index');
    expect($ackIndexes)->toContain('cp_notification_acknowledgements_user_id_index');
    expect($ackIndexes)->toContain('cp_notification_acknowledgements_acknowledged_at_index');
    expect($snoozeIndexes)->toContain('cp_notification_snoozes_notification_id_index');
    expect($snoozeIndexes)->toContain('cp_notification_snoozes_user_id_index');
    expect($snoozeIndexes)->toContain('cp_notification_snoozes_snoozed_until_index');

    expect(collect(Schema::getIndexes(EloquentAcknowledgementRepository::TABLE))->contains(
        fn (array $index): bool => $index['unique'] && $index['columns'] === ['notification_id', 'user_id'],
    ))->toBeTrue();
    expect(collect(Schema::getIndexes(EloquentSnoozeRepository::TABLE))->contains(
        fn (array $index): bool => $index['unique'] && $index['columns'] === ['notification_id', 'user_id'],
    ))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable(EloquentAcknowledgementRepository::TABLE))->toBeFalse();
    expect(Schema::hasTable(EloquentSnoozeRepository::TABLE))->toBeFalse();
});

test('acknowledgements are unique per notification and user', function () {
    recordTablesMigration()->up();
    $record = [
        'id' => 'ack-1',
        'notification_id' => 'notice-1',
        'user_id' => 'user-1',
        'acknowledged_at' => '2026-08-12T10:00:00+12:00',
    ];

    DB::table(EloquentAcknowledgementRepository::TABLE)->insert($record);

    $this->expectException(QueryException::class);
    DB::table(EloquentAcknowledgementRepository::TABLE)->insert([...$record, 'id' => 'ack-2']);
});

test('snoozes are unique per notification and user', function () {
    recordTablesMigration()->up();
    $record = [
        'notification_id' => 'notice-1',
        'user_id' => 'user-1',
        'snoozed_until' => '2026-08-13T10:00:00+12:00',
    ];

    DB::table(EloquentSnoozeRepository::TABLE)->insert($record);

    $this->expectException(QueryException::class);
    DB::table(EloquentSnoozeRepository::TABLE)->insert($record);
});

test('database uniqueness survives concurrent acknowledgement attempts', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The pcntl extension is required for the concurrency test.');
    }

    $path = sys_get_temp_dir().'/cp-notifications-db-race-'.bin2hex(random_bytes(4)).'.sqlite';
    $database = new \PDO('sqlite:'.$path);
    $database->exec('PRAGMA journal_mode=WAL');
    $database->exec('CREATE TABLE acknowledgements (
            id TEXT PRIMARY KEY,
            notification_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            acknowledged_at TEXT NOT NULL,
            UNIQUE (notification_id, user_id)
        )');
    $children = [];

    foreach (range(1, 6) as $attempt) {
        $pid = pcntl_fork();

        if ($pid === 0) {
            $connection = new \PDO('sqlite:'.$path);
            $connection->setAttribute(\PDO::ATTR_TIMEOUT, 5);
            $statement = $connection->prepare(
                'INSERT OR IGNORE INTO acknowledgements
                    (id, notification_id, user_id, acknowledged_at) VALUES (?, ?, ?, ?)',
            );
            $statement->execute([
                'ack-'.$attempt,
                'notice-concurrent',
                'user-concurrent',
                '2026-08-12T10:00:0'.$attempt.'+12:00',
            ]);
            exit(0);
        }

        expect($pid)->toBeGreaterThan(0);
        $children[] = $pid;
    }

    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        expect(pcntl_wifexited($status))->toBeTrue();
        expect(pcntl_wexitstatus($status))->toBe(0);
    }

    expect((int) $database->query('SELECT COUNT(*) FROM acknowledgements')->fetchColumn())->toBe(1);
    $winner = $database->query('SELECT * FROM acknowledgements')->fetch(\PDO::FETCH_ASSOC);
    expect($winner['notification_id'])->toBe('notice-concurrent');
    expect($winner['user_id'])->toBe('user-concurrent');
    $database = null;
    @unlink($path);
    @unlink($path.'-shm');
    @unlink($path.'-wal');
});

function recordTablesMigration(): Migration
{
    return require __DIR__.'/../database/migrations/2026_08_12_000000_create_cp_notification_records_tables.php';
}
