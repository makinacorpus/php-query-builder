<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class PdoTestCase extends FunctionalTestCase
{
    private ?\PDO $connection = null;

    /** @after */
    final protected function closePdoConnection(): void
    {
        if ($this->connection) {
            unset($this->connection);
        }
        if ($this->privConnection) {
            unset($this->privConnection);
        }
    }

    /**
     * Get connection.
     */
    final protected function getPdoConnection(): \PDO
    {
        return $this->connection ??= $this->createPdoConnection();
    }

    /**
     * Create query builder.
     */
    final protected function getQueryBuilder(): PdoQueryBuilder
    {
        return new PdoQueryBuilder($this->getPdoConnection());
    }

    /**
     * Initialize database.
     */
    private function initializeDatabase(string $dbname): void
    {
        try {
            $escaper = new StandardEscaper();
            $params = $this->getPriviledgedConnectionParameters();

            $privConnection = match ($params['driver']) {
                'pdo_mysql' => $this->createPdoConnectionMySQL($params),
                'pdo_pgsql' => $this->createPdoConnectionPostgreSQL($params),
                default => self::markTestSkipped("Unsupported 'DBAL_DRIVER' value '%s' for doctrine/dbal bridge.", $params['driver']),
            };

            \assert($privConnection instanceof \PDO);
            $privConnection->exec("CREATE DATABASE " . $escaper->escapeIdentifier('test_db'));
        } catch (\Throwable $e) {
            // Check database already exists or not.
            if (!\str_contains($e->getMessage(), 'exist')) {
                throw $e;
            }
        } finally {
            unset($privConnection);
        }
    }

    /**
     * Create connection.
     */
    private function createPdoConnection(): \PDO
    {
        $params = $this->getConnectionParameters();

        if ($params['dbname']) {
            $this->initializeDatabase($params['dbname']);
        }

        return match ($params['driver']) {
            'pdo_mysql' => $this->createPdoConnectionMySQL($params),
            'pdo_pgsql' => $this->createPdoConnectionPostgreSQL($params),
            default => self::markTestSkipped("Unsupported 'DBAL_DRIVER' value '%s' for doctrine/dbal bridge.", $params['driver']),
        };
    }

    private function createPdoConnectionMySQL(array $params): \PDO
    {
        $dsn = [];
        if ($value = $params['host']) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = $params['port']) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = $params['dbname']) {
            $dsn[] = 'dbname=' . $value;
        }

        return new \PDO('mysql:' . \implode(';', $dsn), $params['user'] ?? null, $params['password'] ?? null);
    }

    private function createPdoConnectionPostgreSQL(array $params): \PDO
    {
        $dsn = [];
        if ($value = $params['host']) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = $params['port']) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = $params['dbname']) {
            $dsn[] = 'dbname=' . $value;
        }

        return new \PDO('pgsql:' . \implode(';', $dsn), $params['user'] ?? null, $params['password'] ?? null);
    }
}
