<?php

namespace Teoprayoga\Teorion\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Teoprayoga\Teorion\TeorionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrations();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TeorionServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('teorion.strict_mode', false);
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/migrations');
    }
}
