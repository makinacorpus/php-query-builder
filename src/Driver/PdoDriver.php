<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Driver;

use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\SqlString;

class PdoDriver extends AbstractDriver
{
    public function __construct(
        private \PDO $connection
    ) {}

    /**
     * Get server name.
     */
    protected function getServerName(): ?string
    {
        if ($ret = parent::getServerName()) {
            return $ret;
        }

        $serverName = \strtolower($this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME));

        if (\str_contains($serverName, 'mysql')) {
            $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if (\preg_match('@maria@i', $rawVersion)) {
                return 'mariadb';
            }
            return 'mysql';
        }

        return $serverName;
    }

    /**
     * Get server name.
     */
    protected function getServerVersion(): ?string
    {
        if ($ret = parent::getServerVersion()) {
            return $ret;
        }

        $serverName = \strtolower($this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);

        $matches = [];

        // PostgreSQL Example: 16.0 (Debian 16.0-1.pgdg120+1)
        if (\str_contains($serverName, 'pg') || \str_contains($serverName, 'postgres')) {
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
        $serverName = \strtolower($this->getServerName());

        if (\str_contains($serverName, 'mysql')) {
            return new PdoMySQLEscaper($this->connection);
        }

        if (\str_contains($serverName, 'maria')) {
            return new PdoMySQLEscaper($this->connection);
        }

        return new PdoEscaper($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(SqlString $query): mixed
    {
        $prepared = $this->connection->prepare($query->toString());
        $prepared->execute($query->getArguments()->getAll());

        return $prepared;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(SqlString $query): int
    {
        $prepared = $this->connection->prepare($query->toString());
        $prepared->execute($query->getArguments()->getAll());

        return $prepared->rowCount();
    }
}
