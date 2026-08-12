<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Repositories\RepositoryDriverResolver;
use InvalidArgumentException;

class RepositoryDriverResolverTest extends TestCase
{
    public function test_explicit_supported_drivers_are_preserved(): void
    {
        $resolver = new RepositoryDriverResolver;

        $this->assertSame('file', $resolver->resolve('file', true));
        $this->assertSame('eloquent', $resolver->resolve('eloquent', false));
    }

    public function test_auto_uses_the_eloquent_driver_when_installed(): void
    {
        $resolver = new RepositoryDriverResolver;

        $this->assertSame('eloquent', $resolver->resolve('auto', true));
        $this->assertSame('file', $resolver->resolve('auto', false));
    }

    public function test_auto_inspects_composer_when_no_detection_override_is_given(): void
    {
        $resolver = new RepositoryDriverResolver;

        $this->assertSame(
            \Composer\InstalledVersions::isInstalled('statamic/eloquent-driver') ? 'eloquent' : 'file',
            $resolver->resolve('auto'),
        );
    }

    public function test_unknown_drivers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CP Notifications repository driver [memory].');

        (new RepositoryDriverResolver)->resolve('memory');
    }
}
