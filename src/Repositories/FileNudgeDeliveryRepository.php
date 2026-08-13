<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\NudgeDeliveryRepository;
use Ghijk\CpNotifications\Data\NudgeDelivery;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class FileNudgeDeliveryRepository implements NudgeDeliveryRepository
{
    public function __construct(private Filesystem $files, private string $storagePath) {}

    public function find(string $notificationId, string $userId): ?NudgeDelivery
    {
        $path = $this->path($notificationId, $userId);

        return $this->files->exists($path)
            ? NudgeDelivery::fromArray(Yaml::parse($this->files->get($path)))
            : null;
    }

    public function recordSent(string $notificationId, string $userId, ?CarbonImmutable $sentAt = null): NudgeDelivery
    {
        $path = $this->path($notificationId, $userId);
        $this->files->ensureDirectoryExists(dirname($path));
        $lock = fopen($path.'.lock', 'c+');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('Unable to lock notification nudge delivery record.');
        }

        try {
            $existing = $this->find($notificationId, $userId);
            $delivery = new NudgeDelivery(
                $notificationId,
                $userId,
                $sentAt ?? CarbonImmutable::now(),
                ($existing?->sendCount ?? 0) + 1,
            );
            $temporary = tempnam(dirname($path), '.pending-');

            if ($temporary === false
                || $this->files->put($temporary, Yaml::dump($delivery->toArray(), 2, 2), true) === false
                || ! rename($temporary, $path)) {
                throw new RuntimeException('Unable to write notification nudge delivery record.');
            }

            return $delivery;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function path(string $notificationId, string $userId): string
    {
        return $this->storagePath.'/nudge-deliveries/'.$this->segment($notificationId).'/'.$this->segment($userId).'.yaml';
    }

    private function segment(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
