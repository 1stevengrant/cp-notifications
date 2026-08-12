<?php

namespace Ghijk\CpNotifications\Repositories;

use Composer\InstalledVersions;
use InvalidArgumentException;

class RepositoryDriverResolver
{
    public function resolve(string $configuredDriver, ?bool $eloquentDriverInstalled = null): string
    {
        if (in_array($configuredDriver, ['file', 'eloquent'], true)) {
            return $configuredDriver;
        }

        if ($configuredDriver !== 'auto') {
            throw new InvalidArgumentException(
                "Unsupported CP Notifications repository driver [{$configuredDriver}].",
            );
        }

        $installed = $eloquentDriverInstalled
            ?? InstalledVersions::isInstalled('statamic/eloquent-driver');

        return $installed ? 'eloquent' : 'file';
    }
}
