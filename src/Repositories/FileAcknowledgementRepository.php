<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class FileAcknowledgementRepository implements AcknowledgementRepository
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $storagePath,
        private readonly ?AtomicFileWriter $writer = null,
    ) {
    }

    public function find(string $notificationId, string $userId): ?Acknowledgement
    {
        $path = $this->path($notificationId, $userId);

        return $this->files->exists($path) ? $this->read($path) : null;
    }

    public function record(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $acknowledgedAt = null,
    ): Acknowledgement {
        if ($existing = $this->find($notificationId, $userId)) {
            return $existing;
        }

        $acknowledgement = new Acknowledgement(
            id: (string) Str::uuid(),
            notificationId: $notificationId,
            userId: $userId,
            acknowledgedAt: $acknowledgedAt ?? CarbonImmutable::now(),
        );
        $path = $this->path($notificationId, $userId);
        $created = ($this->writer ?? new AtomicFileWriter($this->files))->create(
            $path,
            Yaml::dump($acknowledgement->toArray(), 2, 2),
        );

        return $created ? $acknowledgement : $this->read($path);
    }

    public function forNotification(string $notificationId): Collection
    {
        $directory = $this->storagePath.'/acks/'.$this->segment($notificationId);

        return $this->readFiles($this->files->isDirectory($directory) ? $this->files->files($directory) : []);
    }

    public function forUser(string $userId): Collection
    {
        $suffix = '/'.$this->segment($userId).'.yaml';
        $directory = $this->storagePath.'/acks';
        $paths = $this->files->isDirectory($directory) ? $this->files->allFiles($directory) : [];

        return $this->readFiles(array_filter($paths, fn ($path): bool => str_ends_with($path->getPathname(), $suffix)));
    }

    private function path(string $notificationId, string $userId): string
    {
        return $this->storagePath.'/acks/'.$this->segment($notificationId).'/'.$this->segment($userId).'.yaml';
    }

    private function segment(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function read(string $path): Acknowledgement
    {
        return Acknowledgement::fromArray(Yaml::parse($this->files->get($path)));
    }

    /** @return Collection<int, Acknowledgement> */
    private function readFiles(iterable $paths): Collection
    {
        return collect($paths)
            ->map(fn ($path): Acknowledgement => $this->read((string) $path))
            ->sortBy(fn (Acknowledgement $acknowledgement): int => $acknowledgement->acknowledgedAt->getTimestamp())
            ->values();
    }
}
