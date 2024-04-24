<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\PassthroughErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter\PdoMySQLErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter\PdoPostgreSQLErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter\PdoSQLiteErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter\PdoSQLServerErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper\PdoMySQLEscaper;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\Server\UnableToConnectError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Result\IterableResult;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Vendor;

/**
 * @deprecated This will be removed in 2.0, use PdoBridge instead.
 * @see PdoBridge
 */
class PdoQueryBuilder extends AbstractBridge
{
    private ?\PDO $connection = null;

    public function __construct(
        \PDO $connection
    ) {
        parent::__construct();

        $this->connection = $connection;

        if (static::class === self::class) {
            @\trigger_error(\sprintf("Class '%s' is deprecated and will be removed in 2.0, use '%s' instead.", PdoQueryBuilder::class, PdoBridge::class));
        }
    }

    /**
     * Please override.
     */
    protected function createErrorConverter(): ErrorConverter
    {
        return match ($this->getVendorName()) {
            Vendor::MARIADB => new PdoMySQLErrorConverter(),
            Vendor::MYSQL => new PdoMySQLErrorConverter(),
            Vendor::POSTGRESQL => new PdoPostgreSQLErrorConverter(),
            Vendor::SQLITE => new PdoSQLiteErrorConverter(),
            Vendor::SQLSERVER => new PdoSQLServerErrorConverter(),
            default => new PassthroughErrorConverter(),
        };
    }

    #[\Override]
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

    #[\Override]
    protected function lookupServerVersion(): ?string
    {
        $this->dieIfClosed();

        $rawVersion = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $vendorName = $this->getVendorName();

        $matches = [];

        // PostgreSQL Example: 16.0 (Debian 16.0-1.pgdg120+1)
        if (Vendor::POSTGRESQL === $vendorName) {
            if (\preg_match('@^(\d+\.\d+(\.\d+))@i', $rawVersion, $matches)) {
                return $matches[1];
            }
        }

        // MariaDB example: "11.1.2-MariaDB-1:11.1.2+maria~ubu2204"
        if (\preg_match('@(\d+\.\d+(\.\d+|))-MariaDB@i', $rawVersion, $matches)) {
            return $matches[1];
        }

        // Last resort version string lookup.
        if (\preg_match('@(\d+\.\d+(\.\d+))@i', $rawVersion, $matches)) {
            return $matches[1];
        }

        return null;
    }

    #[\Override]
    protected function createEscaper(): Escaper
    {
        $this->dieIfClosed();

        return match ($this->getVendorName()) {
            Vendor::MARIADB => new PdoMySQLEscaper($this->connection),
            Vendor::MYSQL => new PdoMySQLEscaper($this->connection),
            default => new PdoEscaper($this->connection),
        };
    }

    #[\Override]
    protected function doExecuteQuery(string $expression, array $arguments = []): Result
    {
        $this->dieIfClosed();

        $statement = $this->connection->prepare($expression, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->execute($arguments);

        $result = new IterableResult(
            $statement->getIterator(),
            $statement->rowCount(),
            fn () => $statement->closeCursor(),
        );
        $result->setConverter($this->getConverter());

        return $result;
    }

    #[\Override]
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        $this->dieIfClosed();

        $statement = $this->connection->prepare($expression, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $statement->execute($arguments);

        return $statement->rowCount();
    }

    #[\Override]
    public function close(): void
    {
        $this->connection = null;
    }

    #[\Override]
    public function connect(): void
    {
        throw new UnableToConnectError("PDO driver does not support opening the connection.");
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
