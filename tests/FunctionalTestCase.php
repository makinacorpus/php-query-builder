<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use Doctrine\DBAL\Result;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Bridge\Bridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Raw;

abstract class FunctionalTestCase extends UnitTestCase
{
    private ?Bridge $connection = null;
    private ?Bridge $privConnection = null;

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
    final protected function getBridge(): Bridge
    {
        return $this->connection ??= $this->createBridge();
    }

    /**
     * Create priviledged query builder.
     */
    final protected function getPriviledgedBridge(): Bridge
    {
        return $this->privConnection ??= $this->createPriviledgeBridge();
    }

    /**
     * Really create query builder connection.
     */
    abstract protected function doCreateBridge(array $params): Bridge;

    /**
     * Pass a raw string or query and execute statement over the bridge.
     *
     * This doesn't return any result; but may return affected row count.
     *
     * This is a proxy function to $this->getBridge()->executeStatement();
     */
    protected function executeStatement(string|Expression $query, ?array $arguments = null): ?int
    {
        try {
            return $this->getBridge()->executeStatement($query, $arguments);
        } catch (\Throwable $e) {
            throw new QueryBuilderError(
                \sprintf(
                    <<<TXT
                    Error when executing query, error is: %s
                    Query was:
                    %s
                    TXT,
                    $e->getMessage(),
                    $this->getBridge()->getWriter()->prepare(\is_string($query) ? new Raw($query, $arguments) : $query)->toString()
                ),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Pass a raw string or query and execute statement over the bridge.
     *
     * This doesn't return any result; but may return affected row count.
     *
     * This is a proxy function to $this->getBridge()->executeStatement();
     */
    protected function executeDoctrineQuery(string|Expression $query, ?array $arguments = null): Result
    {
        $bridge = $this->getBridge();
        if (!$bridge instanceof DoctrineQueryBuilder) {
            throw new \LogicException("This method can only be called with a doctrine bridge.");
        }

        try {
            return $bridge->executeQuery($query, $arguments);
        } catch (\Throwable $e) {
            throw new QueryBuilderError(
                \sprintf(
                    <<<TXT
                    Error when executing query, error is: %s
                    Query was:
                    %s
                    TXT,
                    $e->getMessage(),
                    $this->getBridge()->getWriter()->prepare(\is_string($query) ? new Raw($query, $arguments) : $query)->toString()
                ),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Skip for given database.
     */
    protected function skipIfDatabase(string $database, ?string $message = null): void
    {
        if ($this->getBridge()->getServerFlavor() === $database) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database.
     */
    protected function skipIfDatabaseNot(string $database, ?string $message = null): void
    {
        if ($this->getBridge()->getServerFlavor() !== $database) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database, and greater than version.
     */
    protected function skipIfDatabaseGreaterThan(string $database, string $version, ?string $message = null): void
    {
        $this->skipIfDatabaseNot($database);

        if ($this->getBridge()->isVersionGreaterOrEqualThan($version)) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version >= '%s'", $database, $version));
        }
    }

    /**
     * Skip for given database, and lower than version.
     */
    protected function skipIfDatabaseLessThan(string $database, string $version, ?string $message = null): void
    {
        $this->skipIfDatabaseNot($database);

        if ($this->getBridge()->isVersionLessThan($version)) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version <= '%s'", $database, $version));
        }
    }

    /**
     * Create connection.
     */
    private function createBridge(): Bridge
    {
        $params = $this->getConnectionParameters();

        if (!\str_contains($params['driver'],  'sqlite')) {
            if ($params['dbname']) {
                $this->initializeDatabase($params['dbname']);
            }
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
            $privBridge->executeStatement("CREATE DATABASE ?::identifier", ['test_db']);
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
    private function createPriviledgeBridge(): Bridge
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

        $driverOptions = [];
        if (\str_contains($driver,  'sqlsrv')) {
            // https://stackoverflow.com/questions/71688125/odbc-driver-18-for-sql-serverssl-provider-error1416f086
            $driverOptions['TrustServerCertificate'] = "true";
        }

        return \array_filter([
            'driver' => $driver,
            'driverOptions' => $driverOptions,
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

        $driverOptions = [];
        if (\str_contains($driver,  'sqlsrv')) {
            // https://stackoverflow.com/questions/71688125/odbc-driver-18-for-sql-serverssl-provider-error1416f086
            $driverOptions['TrustServerCertificate'] = "true";
        }

        return \array_filter([
            'dbname' => 'test_db',
            'driver' => $driver,
            'driverOptions' => $driverOptions,
            'host' => \getenv('DBAL_HOST'),
            'password' => \getenv('DBAL_PASSWORD'),
            'port' => \getenv('DBAL_PORT'),
            'user' => \getenv('DBAL_USER'),
        ]);
    }
}