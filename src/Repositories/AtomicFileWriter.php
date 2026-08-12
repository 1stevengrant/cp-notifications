<?php

namespace Ghijk\CpNotifications\Repositories;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class AtomicFileWriter
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    public function create(string $path, string $contents): bool
    {
        $directory = dirname($path);
        $this->files->ensureDirectoryExists($directory);
        $temporaryPath = tempnam($directory, '.pending-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary record in [{$directory}].");
        }

        try {
            if ($this->files->put($temporaryPath, $contents, true) === false) {
                throw new RuntimeException("Unable to write temporary record [{$temporaryPath}].");
            }

            if (@link($temporaryPath, $path)) {
                return true;
            }

            if ($this->files->exists($path)) {
                return false;
            }

            throw new RuntimeException("Unable to publish record [{$path}].");
        } finally {
            $this->files->delete($temporaryPath);
        }
    }
}
