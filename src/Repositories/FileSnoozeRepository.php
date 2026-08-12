<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;

class FileSnoozeRepository implements SnoozeRepository
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $storagePath,
        private readonly ?AtomicFileWriter $writer = null,
    ) {
    }

    public function find(string $notificationId, string $userId): ?Snooze
    {
        $path = $this->path($notificationId, $userId);

        return $this->files->exists($path) ? $this->read($path) : null;
    }

    public function record(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $snoozedUntil = null,
    ): Snooze {
        if ($existing = $this->find($notificationId, $userId)) {
            return $existing;
        }

        $snooze = new Snooze(
            notificationId: $notificationId,
            userId: $userId,
            snoozedUntil: $snoozedUntil ?? CarbonImmutable::now()->addDay(),
        );
        $path = $this->path($notificationId, $userId);
        $created = ($this->writer ?? new AtomicFileWriter($this->files))->create(
            $path,
            Yaml::dump($snooze->toArray(), 2, 2),
        );

        return $created ? $snooze : $this->read($path);
    }

    public function forNotification(string $notificationId): Collection
    {
        $directory = $this->storagePath.'/snoozes/'.$this->segment($notificationId);

        return $this->readFiles($this->files->isDirectory($directory) ? $this->files->files($directory) : []);
    }

    public function forUser(string $userId): Collection
    {
        $suffix = '/'.$this->segment($userId).'.yaml';
        $directory = $this->storagePath.'/snoozes';
        $paths = $this->files->isDirectory($directory) ? $this->files->allFiles($directory) : [];

        return $this->readFiles(array_filter($paths, fn ($path): bool => str_ends_with($path->getPathname(), $suffix)));
    }

    private function path(string $notificationId, string $userId): string
    {
        return $this->storagePath.'/snoozes/'.$this->segment($notificationId).'/'.$this->segment($userId).'.yaml';
    }

    private function segment(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function read(string $path): Snooze
    {
        return Snooze::fromArray(Yaml::parse($this->files->get($path)));
    }

    /** @return Collection<int, Snooze> */
    private function readFiles(iterable $paths): Collection
    {
        return collect($paths)
            ->map(fn ($path): Snooze => $this->read((string) $path))
            ->sortBy(fn (Snooze $snooze): int => $snooze->snoozedUntil->getTimestamp())
            ->values();
    }
}
