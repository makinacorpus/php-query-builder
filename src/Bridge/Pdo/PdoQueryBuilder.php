<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoMySQLEscaper;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Result\IterableResult;
use MakinaCorpus\QueryBuilder\Result\Result;

class PdoQueryBuilder extends AbstractBridge
{
    private ?\PDO $connection = null;

    public function __construct(
        \PDO $connection
    ) {
        parent::__construct();

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function lookupServerName(): ?string
    {
        $this->dieIfClosed();

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
        $this->dieIfClosed();

        $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $serverFlavor = $this->getServerFlavor();

        $matches = [];

        // PostgreSQL Example: 16.0 (Debian 16.0-1.pgdg120+1)
        if (Platform::POSTGRESQL === $serverFlavor) {
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
        $this->dieIfClosed();

        return match ($this->getServerFlavor()) {
            Platform::MARIADB => new PdoMySQLEscaper($this->connection),
            Platform::MYSQL => new PdoMySQLEscaper($this->connection),
            default => new PdoEscaper($this->connection),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteQuery(string $expression, array $arguments = []): Result
    {
        $this->dieIfClosed();

        $statement = $this->connection->prepare($expression);
        $statement->execute($arguments);

        return new IterableResult(
            $statement->getIterator(),
            $statement->rowCount(),
            fn () => $statement->closeCursor(),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        $this->dieIfClosed();

        $statement = $this->connection->prepare($expression);
        $statement->execute($arguments);

        return $statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Die if closed.
     */
    protected function dieIfClosed(): void
    {
        if (null === $this->connection) {
            throw new QueryBuilderError("Connection was closed.");
        }
    }

    /**
     * Get connection.
     *
     * @internal
     */
    public function getConnection(): \PDO
    {
        $this->dieIfClosed();

        return $this->connection;
    }
}
