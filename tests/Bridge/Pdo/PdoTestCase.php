<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class PdoTestCase extends FunctionalTestCase
{
    /**
     * Get query builder.
     */
    protected function getQueryBuilder(): PdoQueryBuilder
    {
        return $this->getBridge();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateBridge(array $params): AbstractBridge
    {
        return new PdoQueryBuilder(
            match ($params['driver']) {
                'pdo_mysql' => $this->createPdoConnectionMySQL($params),
                'pdo_pgsql' => $this->createPdoConnectionPostgreSQL($params),
                default => self::markTestSkipped("Unsupported 'DBAL_DRIVER' value '%s' for doctrine/dbal bridge.", $params['driver']),
            },
        );
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
