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
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Query\PdoRawQuery;
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
        return match ($this->getServerFlavor()) {
            self::SERVER_MARIADB => new PdoMySQLEscaper($this->connection),
            self::SERVER_MYSQL => new PdoMySQLEscaper($this->connection),
            default => new PdoEscaper($this->connection),
        };
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
     * Create SELECT query builder.
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): PdoSelect
    {
        $ret = new PdoSelect($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create UPDATE query.
     */
    public function update(string|Expression $table, ?string $alias = null): PdoUpdate
    {
        $ret = new PdoUpdate($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create INSERT query.
     */
    public function insert(string|Expression $table): PdoInsert
    {
        $ret = new PdoInsert($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create MERGE query.
     */
    public function merge(string|Expression $table): PdoMerge
    {
        $ret = new PdoMerge($table);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create DELETE query.
     */
    public function delete(string|Expression $table, ?string $alias = null): PdoDelete
    {
        $ret = new PdoDelete($table, $alias);
        $ret->initialize($this);

        return $ret;
    }

    /**
     * Create raw SQL query.
     */
    public function raw(string $expression = null, mixed $arguments = null, bool $returns = false): PdoRawQuery
    {
        $ret = new PdoRawQuery($expression, $arguments, $returns);
        $ret->initialize($this);

        return $ret;
    }
}
