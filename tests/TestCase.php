<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $this->assertDeclaredTestingEnvironment();

        $configurationCache = dirname(__DIR__) . '/bootstrap/cache/config.php';

        if (is_file($configurationCache)) {
            throw new LogicException(
                'Refusing to run tests while Laravel configuration is cached. Clear the configuration cache before testing.'
            );
        }

        $app = parent::createApplication();

        $this->assertRuntimeTestingEnvironment($app);

        return $app;
    }

    private function assertDeclaredTestingEnvironment(): void
    {
        if (
            $this->environmentValue('APP_ENV') !== 'testing'
            || $this->environmentValue('DB_CONNECTION') !== 'sqlite'
            || $this->environmentValue('DB_DATABASE') !== ':memory:'
        ) {
            throw new LogicException(
                'Refusing to run tests outside the required isolated SQLite in-memory testing environment.'
            );
        }
    }

    private function assertRuntimeTestingEnvironment(Application $app): void
    {
        if (
            ! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new LogicException(
                'Refusing to run tests because Laravel resolved a non-isolated database connection.'
            );
        }
    }

    private function environmentValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) ? $value : null;
    }
}
