<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\OCI8\Middleware\InitializeSession;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class DoctrineTestCase extends FunctionalTestCase
{
    private ?Connection $connection = null;

    /** @after */
    final protected function closeDoctrineConnection(): void
    {
        if ($this->connection) {
            unset($this->connection);
        }
    }

    /**
     * Get doctrine connection.
     */
    final protected function getDoctrineConnection(): Connection
    {
        return $this->connection ??= $this->createDoctrineConnection();
    }

    /**
     * Create query builder.
     */
    final protected function getQueryBuilder(): DoctrineQueryBuilder
    {
        return new DoctrineQueryBuilder($this->getDoctrineConnection());
    }

    /**
     * Create doctrine/dbal connection.
     */
    private function createDoctrineConnection(): Connection
    {
        $params = $this->getConnectionParameters();

        return DriverManager::getConnection(
            $params,
            $this->createDoctrineConfiguration($params['driver']),
        );
    }

    /**
     * Code copied from doctrine/dbal package.
     *
     * @see \Doctrine\DBAL\Tests\FunctionalTestCase
     */
    private function createDoctrineConfiguration(string $driver): Configuration
    {
        $configuration = new Configuration();

        switch ($driver) {
            case 'pdo_oci':
            case 'oci8':
                $configuration->setMiddlewares([new InitializeSession()]);
                break;
            case 'pdo_sqlite':
            case 'sqlite3':
                $configuration->setMiddlewares([new EnableForeignKeys()]);
                break;
        }

        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }
}
