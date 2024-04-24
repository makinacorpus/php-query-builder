<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\ErrorConverter\DoctrineErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineEscaper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper\DoctrineMySQLEscaper;
use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Result\IterableResult;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Vendor;
use MakinaCorpus\QueryBuilder\Writer\Writer;

/**
 * @deprecated This will be removed in 2.0, use DoctrineBridge instead.
 * @see DoctrineBridge
 */
class DoctrineQueryBuilder extends AbstractBridge
{
    private Connection $connection;
    private ?string $doctrineServerVersion = null;

    public function __construct(
        Connection $connection,
    ) {
        parent::__construct();

        $this->connection = $connection;

        if (static::class === self::class) {
            @\trigger_error(\sprintf("Class '%s' is deprecated and will be removed in 2.0, use '%s' instead.", DoctrineQueryBuilder::class, DoctrineBridge::class));
        }
    }

    /**
     * Please override.
     */
    protected function createErrorConverter(): ErrorConverter
    {
        return new DoctrineErrorConverter();
    }

    #[\Override]
    protected function lookupServerName(): ?string
    {
        $params = $this->connection->getParams();

        $userServerVersion = $params['serverVersion'] ?? $params['primary']['serverVersion'] ?? null;

        if ($userServerVersion && \preg_match('/[a-z]+/', $userServerVersion)) {
            return $userServerVersion;
        }

        return $params['driver'] . ' - ' . $this->lookupServerVersion();
    }

    #[\Override]
    protected function lookupServerVersion(): ?string
    {
        if (null !== $this->doctrineServerVersion) {
            return $this->doctrineServerVersion;
        }

        // doctrine/dbal:^3.17 only.
        $driver = $this->connection->getDriver();
        if (\method_exists($driver, 'getServerVersion')) {
            return $this->doctrineServerVersion = $driver->getServerVersion();
        }
        if (\method_exists($this->connection, 'getWrappedConnection')) {
            $driverConnection = $this->connection->getWrappedConnection();
            if (\method_exists($driverConnection, 'getServerVersion')) {
                return $this->doctrineServerVersion = $driverConnection->getServerVersion();
            }
        }

        // doctrine/dbal:^4.0 only.
        if (\method_exists($this->connection, 'getServerVersion')) {
            return $this->doctrineServerVersion = $this->connection->getServerVersion();
        }

        return $this->doctrineServerVersion = '0.0.0';
    }

    #[\Override]
    protected function createEscaper(): Escaper
    {
        return match ($this->getVendorName()) {
            Vendor::MARIADB => new DoctrineMySQLEscaper($this->connection),
            Vendor::MYSQL => new DoctrineMySQLEscaper($this->connection),
            default => new DoctrineEscaper($this->connection),
        };
    }

    #[\Override]
    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        // @todo Temporary deactivated, needs a way to add converter
        //   locally for the given converter without polluting global
        //   converter plugin registry.
        // $converter->register(new DoctrineInputConverter($this->connection));

        return parent::createWriter($escaper, $converter);
    }

    #[\Override]
    protected function doExecuteQuery(string $expression, array $arguments = []): Result
    {
        $doctrineResult = $this->connection->executeQuery($expression, $arguments);

        $result = new IterableResult($doctrineResult->iterateAssociative(), $doctrineResult->rowCount(), fn () => $doctrineResult->free());
        $result->setConverter($this->getConverter());

        return $result;
    }

    #[\Override]
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        return (int) $this->connection->executeStatement($expression, $arguments);
    }

    #[\Override]
    public function close(): void
    {
        $this->connection->close();
    }

    #[\Override]
    public function connect(): void
    {
        // Do nothing, because doctrine will lazy-reconnect itself on access.
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
