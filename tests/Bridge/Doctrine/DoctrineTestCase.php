<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\OCI8\Middleware\InitializeSession;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use MakinaCorpus\QueryBuilder\Bridge\Bridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class DoctrineTestCase extends FunctionalTestCase
{
    /**
     * Get query builder.
     */
    protected function getQueryBuilder(): DoctrineQueryBuilder
    {
        return $this->getBridge();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateBridge(array $params): Bridge
    {
        if (\str_contains($params['driver'], 'sqlite')) {
            $params['memory'] = true;
        }

        return new DoctrineQueryBuilder(
            DriverManager::getConnection(
                $params,
                $this->createDoctrineConfiguration($params['driver']),
            ),
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
