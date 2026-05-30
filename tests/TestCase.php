<?php

namespace ApiHub\Laravel\Tests;

use ApiHub\Laravel\ApiHubServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ApiHubServiceProvider::class];
    }
}
