<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
