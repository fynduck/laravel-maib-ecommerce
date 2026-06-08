<?php

namespace Fynduck\MaibEcommerce\Tests;

use Fynduck\MaibEcommerce\MaibServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MaibServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('maib.project_id', 'test-project-id');
        $app['config']->set('maib.project_secret', 'test-project-secret');
        $app['config']->set('maib.signature_key', 'test-signature-key');
        $app['config']->set('maib.base_url', 'https://api.maibmerchants.md/v1/');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
