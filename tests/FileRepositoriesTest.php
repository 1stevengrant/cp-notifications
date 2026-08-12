<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class FileRepositoriesTest extends TestCase
{
    private Filesystem $files;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->storagePath = sys_get_temp_dir().'/cp-notifications-'.Str::uuid();
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_acknowledgements_use_one_yaml_file_and_are_queryable(): void
    {
        $repository = new FileAcknowledgementRepository($this->files, $this->storagePath);
        $firstTime = CarbonImmutable::parse('2026-08-12T10:00:00+12:00');

        $first = $repository->record('notice/1', 'user/1', $firstTime);
        $duplicate = $repository->record('notice/1', 'user/1', $firstTime->addHour());
        $repository->record('notice/1', 'user-2', $firstTime->addHour());

        $paths = $this->files->allFiles($this->storagePath.'/acks');
        $this->assertCount(2, $paths);
        $this->assertSame($first->id, $duplicate->id);
        $this->assertSame('notice/1', Yaml::parse($this->files->get((string) $paths[0]))['notification_id']);
        $this->assertSame($first->id, $repository->find('notice/1', 'user/1')?->id);
        $this->assertCount(2, $repository->forNotification('notice/1'));
        $this->assertCount(1, $repository->forUser('user/1'));
        $this->assertNull($repository->find('missing', 'user/1'));
    }

    public function test_snoozes_use_one_yaml_file_and_are_single_use(): void
    {
        $repository = new FileSnoozeRepository($this->files, $this->storagePath);
        $firstExpiry = CarbonImmutable::parse('2026-08-13T10:00:00+12:00');

        $first = $repository->record('notice/1', 'user/1', $firstExpiry);
        $duplicate = $repository->record('notice/1', 'user/1', $firstExpiry->addDay());
        $repository->record('notice/1', 'user-2', $firstExpiry->addDay());

        $paths = $this->files->allFiles($this->storagePath.'/snoozes');
        $this->assertCount(2, $paths);
        $this->assertTrue($first->snoozedUntil->equalTo($duplicate->snoozedUntil));
        $this->assertSame('notice/1', Yaml::parse($this->files->get((string) $paths[0]))['notification_id']);
        $this->assertNotNull($repository->find('notice/1', 'user/1'));
        $this->assertCount(2, $repository->forNotification('notice/1'));
        $this->assertCount(1, $repository->forUser('user/1'));
        $this->assertNull($repository->find('missing', 'user/1'));
    }
}
