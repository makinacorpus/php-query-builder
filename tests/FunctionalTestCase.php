<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Driver\AbstractDriver;
use MakinaCorpus\QueryBuilder\Driver\Driver;
use MakinaCorpus\QueryBuilder\Driver\PdoDriver;

abstract class FunctionalTestCase extends UnitTestCase
{
    private ?Driver $driver = null;

    /** @after */
    final protected function cleanUpDriver(): void
    {
        if ($this->connection) {
            unset($this->connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    final protected function setUpDriver(): Driver
    {
        if (!$driver = \getenv('DBAL_DRIVER')) {
            self::markTestSkipped("Missing 'DBAL_DRIVER' environment variable.");
        }

        $driver = match ($driver) {
            'pdo_mysql' => $this->createDriverPdoMySQL(),
            'pdo_pgsql' => $this->createDriverPdoPostgreSQL(),
            default => self::markTestSkipped("Invalid 'DBAL_DRIVER' environment variable value."),
        };

        if ($driver instanceof AbstractDriver) {
            $name = $version = null;
            if ($value = \getenv('DBAL_NAME')) {
                $name = $value;
            }
            if ($value = \getenv('DBAL_VERSION')) {
                $version = $value;
            }
            $driver->setServerInfo($name, $version);
        }

        return $driver;
    }

    private function createDriverPdoMySQL(): Driver
    {
        $dsn = [];
        if ($value = \getenv('DBAL_HOST')) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = \getenv('DBAL_PORT')) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = \getenv('DBAL_DBNAME')) {
            $dsn[] = 'dbname=' . $value;
        }

        return new PdoDriver(new \PDO('mysql:' . \implode(';', $dsn), \getenv('DBAL_USER'), \getenv('DBAL_PASSWORD')));
    }

    private function createDriverPdoPostgreSQL(): Driver
    {
        $dsn = [];
        if ($value = \getenv('DBAL_HOST')) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = \getenv('DBAL_PORT')) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = \getenv('DBAL_DBNAME')) {
            $dsn[] = 'dbname=' . $value;
        }

        return new PdoDriver(new \PDO('pgsql:' . \implode(';', $dsn), \getenv('DBAL_USER'), \getenv('DBAL_PASSWORD')));
    }

    /**
     * Get testing connection object.
     */
    final protected function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->getDriver());
    }

    /**
     * Get testing connection object.
     */
    final protected function getDriver(): Driver
    {
        return $this->driver ?? ($this->driver = $this->setUpDriver());
    }
}
