<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineMySQLEscaper;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Result\IterableResult;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class DoctrineQueryBuilder extends AbstractBridge
{
    private ?Connection $connection = null;
    private ?string $doctrineServerVersion = null;

    public function __construct(
        Connection $connection,
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
        $this->dieIfClosed();

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
        $this->dieIfClosed();

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
    protected function doExecuteQuery(string $expression, array $arguments = []): Result
    {
        $this->dieIfClosed();

        $doctrineResult = $this->connection->executeQuery($expression, $arguments);

        $result = new IterableResult($doctrineResult->iterateAssociative(), $doctrineResult->rowCount(), fn () => $doctrineResult->free());
        $result->setConverter($this->getConverter());

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        $this->dieIfClosed();

        return (int) $this->connection->executeStatement($expression, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->connection) {
            $this->connection->close();
        }
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
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
