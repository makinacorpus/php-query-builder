<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Converter\InputConverter\DoctrineInputConverter;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineMySQLEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineDelete;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineInsert;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineMerge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineRawQuery;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineSelect;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineUpdate;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class DoctrineQueryBuilder extends AbstractBridge
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * {@inheritdoc}
     */
    protected function lookupServerName(): ?string
    {
        return $this->connection->getParams()['driver'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function lookupServerVersion(): ?string
    {
        if ($this->connection instanceof ServerInfoAwareConnection) {
            return $this->connection->getServerVersion();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function createEscaper(): Escaper
    {
        return match ($this->getServerFlavor()) {
            self::SERVER_MARIADB => new DoctrineMySQLEscaper($this->connection),
            self::SERVER_MYSQL => new DoctrineMySQLEscaper($this->connection),
            default => new DoctrineEscaper($this->connection),
        };
    }

    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        $converter->register(new DoctrineInputConverter($this->connection));

        return parent::createWriter($escaper, $converter);
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement(string $expression = null, mixed $arguments = null): ?int
    {
        return $this->raw($expression, $arguments)->executeStatement();
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
