<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Testing;

use MakinaCorpus\QueryBuilder\Bridge\Bridge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;

trait FunctionalPdoTestCaseTrait
{
    use FunctionalTestCaseTrait;

    #[\Override]
    protected function doCreateBridge(array $params): Bridge
    {
        return new PdoQueryBuilder(
            match ($params['driver']) {
                'pdo_mysql' => $this->createPdoConnectionMySQL($params),
                'pdo_pgsql' => $this->createPdoConnectionPostgreSQL($params),
                default => self::markTestSkipped("Unsupported 'DBAL_DRIVER' value '%s' for doctrine/dbal bridge.", $params['driver']),
            },
        );
    }

    /**
     * Create MySQL PDO connection.
     */
    private function createPdoConnectionMySQL(array $params): \PDO
    {
        $dsn = [];
        if ($value = ($params['host'] ?? null)) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = ($params['port'] ?? null)) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = ($params['dbname'] ?? null)) {
            $dsn[] = 'dbname=' . $value;
        }

        return new \PDO('mysql:' . \implode(';', $dsn), $params['user'] ?? null, $params['password'] ?? null);
    }

    /**
     * Create PostgreSQL PDO connection.
     */
    private function createPdoConnectionPostgreSQL(array $params): \PDO
    {
        $dsn = [];
        if ($value = ($params['host'] ?? null)) {
            $dsn[] = 'host=' . $value;
        }
        if ($value = ($params['port'] ?? null)) {
            $dsn[] = 'port=' . $value;
        }
        if ($value = ($params['dbname'] ?? null)) {
            $dsn[] = 'dbname=' . $value;
        }

        return new \PDO('pgsql:' . \implode(';', $dsn), $params['user'] ?? null, $params['password'] ?? null);
    }
}
