<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

abstract class FunctionalTestCase extends UnitTestCase
{
    /**
     * Get connection parameters for user with privileges connection.
     *
     * This connection serves the purpose of initializing database.
     */
    final protected function getPriviledgedConnectionParameters(): array
    {
        if (!$driver = \getenv('DBAL_DRIVER')) {
            self::markTestSkipped("Missing 'DBAL_DRIVER' environment variable.");
        }

        return \array_filter([
            'driver' => $driver,
            'host' => \getenv('DBAL_HOST'),
            'password' => \getenv('DBAL_ROOT_PASSWORD'),
            'port' => \getenv('DBAL_PORT'),
            'user' => \getenv('DBAL_ROOT_USER'),
        ]);
    }

    /**
     * Get connection parameters for test user.
     */
    final protected function getConnectionParameters(): array
    {
        if (!$driver = \getenv('DBAL_DRIVER')) {
            self::markTestSkipped("Missing 'DBAL_DRIVER' environment variable.");
        }

        return \array_filter([
            'dbname' => 'test_db',
            'driver' => $driver,
            'host' => \getenv('DBAL_HOST'),
            'password' => \getenv('DBAL_PASSWORD'),
            'port' => \getenv('DBAL_PORT'),
            'user' => \getenv('DBAL_USER'),
        ]);
    }
}
