<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoMySQLEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoDelete;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoInsert;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoMerge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoSelect;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoUpdate;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;

class PdoQueryBuilder extends AbstractBridge
{
    public function __construct(
        private \PDO $connection
    ) {}

    /**
     * {@inheritdoc}
     */
    protected function lookupServerName(): ?string
    {
        $rawServerName = \strtolower($this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME));

        if (\str_contains($rawServerName, 'mysql')) {
            $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if (\preg_match('@maria@i', $rawVersion)) {
                return 'mariadb';
            }
            return 'mysql';
        }

        return $rawServerName;
    }

    /**
     * {@inheritdoc}
     */
    protected function lookupServerVersion(): ?string
    {
        $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $serverFlavor = $this->getServerFlavor();

        $matches = [];

        // PostgreSQL Example: 16.0 (Debian 16.0-1.pgdg120+1)
        if (self::SERVER_POSTGRESQL === $serverFlavor) {
            if (\preg_match('@^(\d+\.\d+(\.\d+))@i', $rawVersion, $matches)) {
                return $matches[1];
            }
        }

        // MariaDB example: "11.1.2-MariaDB-1:11.1.2+maria~ubu2204"
        if (\preg_match('@(\d+\.\d+(\.\d+|))-MariaDB@i', $rawVersion, $matches)) {
            return $matches[1];
        }

        // Last resort version string lookup.
        if (\preg_match('@(\d+\.\d+(\.\d+))@i', $rawVersion)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function createEscaper(): Escaper
    {
        $serverFlavor = $this->getServerFlavor();

        if (self::SERVER_POSTGRESQL === $serverFlavor) {
            return new PdoEscaper($this->connection);
        }

        if (self::SERVER_MARIADB === $serverFlavor) {
            return new PdoMySQLEscaper($this->connection);
        }

        return new PdoEscaper($this->connection);
    }

    /**
     * Get connection.
     *
     * @internal
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): PdoSelect
    {
        $ret = new PdoSelect();
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string|Expression $table, ?string $alias = null): PdoUpdate
    {
        $ret = new PdoUpdate();
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string|Expression $table): PdoInsert
    {
        $ret = new PdoInsert();
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string|Expression $table): PdoMerge
    {
        $ret = new PdoMerge();
        $ret->initialize($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string|Expression $table, ?string $alias = null): PdoDelete
    {
        $ret = new PdoDelete();
        $ret->initialize($this);

        return $ret;
    }
}
