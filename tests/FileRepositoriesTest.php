<?php

namespace Ghijk\CpNotifications\Tests\Pest\FileRepositoriesTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\AtomicFileWriter;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->storagePath = sys_get_temp_dir().'/cp-notifications-'.Str::uuid();
});

afterEach(function () {
    $this->files->deleteDirectory($this->storagePath);

});

test('acknowledgements use one yaml file and are queryable', function () {
    $repository = new FileAcknowledgementRepository($this->files, $this->storagePath);
    $firstTime = CarbonImmutable::parse('2026-08-12T10:00:00+12:00');

    $first = $repository->record('notice/1', 'user/1', $firstTime);
    $duplicate = $repository->record('notice/1', 'user/1', $firstTime->addHour());
    $repository->record('notice/1', 'user-2', $firstTime->addHour());

    $paths = $this->files->allFiles($this->storagePath.'/acks');
    expect($paths)->toHaveCount(2);
    expect($duplicate->id)->toBe($first->id);
    expect(Yaml::parse($this->files->get((string) $paths[0]))['notification_id'])->toBe('notice/1');
    expect($repository->find('notice/1', 'user/1')?->id)->toBe($first->id);
    expect($repository->forNotification('notice/1'))->toHaveCount(2);
    expect($repository->forUser('user/1'))->toHaveCount(1);
    expect($repository->find('missing', 'user/1'))->toBeNull();
});

test('snoozes use one yaml file and are single use', function () {
    $repository = new FileSnoozeRepository($this->files, $this->storagePath);
    $firstExpiry = CarbonImmutable::parse('2026-08-13T10:00:00+12:00');

    $first = $repository->record('notice/1', 'user/1', $firstExpiry);
    $duplicate = $repository->record('notice/1', 'user/1', $firstExpiry->addDay());
    $repository->record('notice/1', 'user-2', $firstExpiry->addDay());

    $paths = $this->files->allFiles($this->storagePath.'/snoozes');
    expect($paths)->toHaveCount(2);
    expect($first->snoozedUntil->equalTo($duplicate->snoozedUntil))->toBeTrue();
    expect(Yaml::parse($this->files->get((string) $paths[0]))['notification_id'])->toBe('notice/1');
    expect($repository->find('notice/1', 'user/1'))->not->toBeNull();
    expect($repository->forNotification('notice/1'))->toHaveCount(2);
    expect($repository->forUser('user/1'))->toHaveCount(1);
    expect($repository->find('missing', 'user/1'))->toBeNull();
});

test('atomic writer publishes only the first complete record', function () {
    $path = $this->storagePath.'/records/record.yaml';
    $writer = new AtomicFileWriter($this->files);

    expect($writer->create($path, "winner: first\n"))->toBeTrue();
    expect($writer->create($path, "winner: second\n"))->toBeFalse();
    expect(Yaml::parseFile($path))->toBe(['winner' => 'first']);
    expect($this->files->glob(dirname($path).'/.pending-*'))->toBe([]);
});

test('file snoozes default to exactly twenty four hours and cannot be reused', function () {
    CarbonImmutable::setTestNow('2026-08-12T10:00:00+12:00');

    try {
        $repository = new FileSnoozeRepository($this->files, $this->storagePath);
        $first = $repository->record('notice-1', 'user-1');

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addHour());
        $second = $repository->record('notice-1', 'user-1');

        expect($first->snoozedUntil->equalTo(CarbonImmutable::parse('2026-08-13T10:00:00+12:00')))->toBeTrue();
        expect($second->snoozedUntil->equalTo($first->snoozedUntil))->toBeTrue();
        expect($this->files->allFiles($this->storagePath.'/snoozes'))->toHaveCount(1);
    } finally {
        CarbonImmutable::setTestNow();
    }
});

test('competing processes leave one complete acknowledgement', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The pcntl extension is required for the concurrency test.');
    }

    $children = [];

    foreach (range(1, 6) as $attempt) {
        $pid = pcntl_fork();

        if ($pid === 0) {
            (new FileAcknowledgementRepository(new Filesystem, $this->storagePath))->record(
                'notice-concurrent',
                'user-concurrent',
                CarbonImmutable::parse("2026-08-12T10:00:0{$attempt}+12:00"),
            );
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

    $records = $this->files->allFiles($this->storagePath.'/acks');
    expect($records)->toHaveCount(1);
    $data = Yaml::parseFile((string) $records[0]);
    expect(array_keys($data))->toBe(['id', 'notification_id', 'user_id', 'acknowledged_at']);
    expect($data['id'])->not->toBeEmpty();
    expect($data['notification_id'])->toBe('notice-concurrent');
    expect($data['user_id'])->toBe('user-concurrent');
    expect(CarbonImmutable::parse($data['acknowledged_at']))->toBeInstanceOf(CarbonImmutable::class);
});
