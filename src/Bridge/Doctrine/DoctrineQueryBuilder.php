<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineDelete;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineInsert;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineMerge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineSelect;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineUpdate;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;

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
        return new DoctrineEscaper($this->connection);
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
     * {@inheritdoc}
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): DoctrineSelect
    {
        $ret = new DoctrineSelect($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string|Expression $table, ?string $alias = null): DoctrineUpdate
    {
        $ret = new DoctrineUpdate($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string|Expression $table): DoctrineInsert
    {
        $ret = new DoctrineInsert($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string|Expression $table): DoctrineMerge
    {
        $ret = new DoctrineMerge($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string|Expression $table, ?string $alias = null): DoctrineDelete
    {
        $ret = new DoctrineDelete($table, $alias);
        $ret->initialize($this);

        return $ret;
    }
}
