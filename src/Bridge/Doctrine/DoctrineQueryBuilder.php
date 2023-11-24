<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineMySQLEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineDelete;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineInsert;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineMerge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineRawQuery;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineSelect;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineUpdate;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class DoctrineQueryBuilder extends AbstractBridge
{
    private ?string $doctrineServerVersion = null;

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * {@inheritdoc}
     */
    protected function lookupServerName(): ?string
    {
        $params = $this->connection->getParams();

        $userServerVersion = $params['serverVersion'] ?? $params['primary']['serverVersion'] ?? null;

        if ($userServerVersion && \preg_match('/[a-z]+/', $userServerVersion)) {
            return $userServerVersion;
        }

        return $params['driver'] . ' - ' . $this->lookupServerVersion();
    }

    /**
     * {@inheritdoc}
     */
    protected function lookupServerVersion(): ?string
    {
        if (null !== $this->doctrineServerVersion) {
            return $this->doctrineServerVersion;
        }

        // doctrine/dbal > 3, maybe, don't really know.
        $driver = $this->connection->getDriver();
        if ((\interface_exists(ServerInfoAwareConnection::class) && $driver instanceof ServerInfoAwareConnection) || \method_exists($driver, 'getServerVersion')) {
            return $this->doctrineServerVersion = $driver->getServerVersion();
        }

        $driverConnection = $this->connection->getWrappedConnection();
        if ((\interface_exists(ServerInfoAwareConnection::class) && $driverConnection instanceof ServerInfoAwareConnection) || \method_exists($driverConnection, 'getServerVersion')) {
            return $this->doctrineServerVersion = $driverConnection->getServerVersion();
        }

        return $this->doctrineServerVersion = 'unknown';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEscaper(): Escaper
    {
        return match ($this->getServerFlavor()) {
            Platform::MARIADB => new DoctrineMySQLEscaper($this->connection),
            Platform::MYSQL => new DoctrineMySQLEscaper($this->connection),
            default => new DoctrineEscaper($this->connection),
        };
    }

    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        // @todo Temporary deactivated, needs a way to add converter
        //   locally for the given converter without polluting global
        //   converter plugin registry.
        // $converter->register(new DoctrineInputConverter($this->connection));

        return parent::createWriter($escaper, $converter);
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        return (int) $this->connection->executeStatement($expression, $arguments);
    }

    /**
     * Execute query and return result.
     */
    public function executeQuery(string|Expression $expression = null, mixed $arguments = null): Result
    {
        if (\is_string($expression)) {
            $expression = new Raw($expression, $arguments);
        } else if ($arguments) {
            throw new QueryBuilderError("Cannot pass \$arguments if \$query is not a string.");
        }

        $prepared = $this->getWriter()->prepare($expression);

        return $this
            ->connection
            ->executeQuery(
                $prepared->toString(),
                $prepared->getArguments()->getAll(),
            )
        ;
    }

    /**
     * Get connection.
     *
     * @internal
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Create SELECT query builder.
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): DoctrineSelect
    {
        $ret = new DoctrineSelect($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create UPDATE query.
     */
    public function update(string|Expression $table, ?string $alias = null): DoctrineUpdate
    {
        $ret = new DoctrineUpdate($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create INSERT query.
     */
    public function insert(string|Expression $table): DoctrineInsert
    {
        $ret = new DoctrineInsert($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create MERGE query.
     */
    public function merge(string|Expression $table): DoctrineMerge
    {
        $ret = new DoctrineMerge($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create DELETE query.
     */
    public function delete(string|Expression $table, ?string $alias = null): DoctrineDelete
    {
        $ret = new DoctrineDelete($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create raw SQL query.
     */
    public function raw(string $expression = null, mixed $arguments = null, bool $returns = false): DoctrineRawQuery
    {
        $ret = new DoctrineRawQuery($expression, $arguments, $returns);
        $ret->initialize($this);

        return $ret;
    }
}
