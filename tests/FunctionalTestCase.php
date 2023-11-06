<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;
use MakinaCorpus\QueryBuilder\Expression\Identifier;

abstract class FunctionalTestCase extends UnitTestCase
{
    private ?AbstractBridge $connection = null;
    private ?AbstractBridge $privConnection = null;

    /** @after */
    final protected function closeConnection(): void
    {
        if ($this->connection) {
            unset($this->connection);
        }
        if ($this->privConnection) {
            unset($this->privConnection);
        }
    }

    /**
     * Create query builder.
     */
    final protected function getBridge(): AbstractBridge
    {
        return $this->connection ??= $this->createBridge();
    }

    /**
     * Create priviledged query builder.
     */
    final protected function getPriviledgedBridge(): AbstractBridge
    {
        return $this->privConnection ??= $this->createPriviledgeBridge();
    }

    /**
     * Really create query builder connection.
     */
    abstract protected function doCreateBridge(array $params): AbstractBridge;

    /**
     * Create connection.
     */
    private function createBridge(): AbstractBridge
    {
        $params = $this->getConnectionParameters();

        if ($params['dbname']) {
            $this->initializeDatabase($params['dbname']);
        }

        return $this->doCreateBridge($params);
    }

    /**
     * Initialize database.
     */
    private function initializeDatabase(string $dbname): void
    {
        $privBridge = $this->getPriviledgedBridge();

        try {
            if ($privBridge instanceof DoctrineQueryBuilder) {
                $privBridge->raw("CREATE DATABASE ?", [new Identifier('test_db')])->executeStatement();
            } else if ($privBridge instanceof PdoQueryBuilder) {
                $privBridge->raw("CREATE DATABASE ?", [new Identifier('test_db')])->executeStatement();
            } else {
                throw new \Exception("Unsupported bridge.");
            }
        } catch (\Throwable $e) {
            // Check database already exists or not.
            if (!\str_contains($e->getMessage(), 'exist')) {
                throw $e;
            }
        }
    }

    /**
     * Create priviledged query builder.
     */
    private function createPriviledgeBridge(): AbstractBridge
    {
        return $this->doCreateBridge($this->getPriviledgedConnectionParameters());
    }

    /**
     * Get connection parameters for user with privileges connection.
     *
     * This connection serves the purpose of initializing database.
     */
    private function getPriviledgedConnectionParameters(): array
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
    private function getConnectionParameters(): array
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