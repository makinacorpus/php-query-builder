<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Converter\ConverterPluginRegistry;
use MakinaCorpus\QueryBuilder\DefaultQueryBuilder;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Converter\MySQLConverter;
use MakinaCorpus\QueryBuilder\Platform\Escaper\MySQLEscaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Schema\MySQLSchemaManager;
use MakinaCorpus\QueryBuilder\Platform\Schema\PostgreSQLSchemaManager;
use MakinaCorpus\QueryBuilder\Platform\Schema\SQLiteSchemaManager;
use MakinaCorpus\QueryBuilder\Platform\Schema\SQLServerSchemaManager;
use MakinaCorpus\QueryBuilder\Platform\Transaction\MySQLTransaction;
use MakinaCorpus\QueryBuilder\Platform\Transaction\PostgreSQLTransaction;
use MakinaCorpus\QueryBuilder\Platform\Transaction\SQLiteTransaction;
use MakinaCorpus\QueryBuilder\Platform\Transaction\SQLServerTransaction;
use MakinaCorpus\QueryBuilder\Platform\Writer\MariaDBWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\SQLiteWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\SQLServerWriter;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;
use MakinaCorpus\QueryBuilder\Vendor;
use MakinaCorpus\QueryBuilder\Writer\Writer;

abstract class AbstractBridge extends DefaultQueryBuilder implements Bridge
{
    private ?Converter $converter = null;
    private ?ConverterPluginRegistry $converterPluginRegistry = null;
    private ?Writer $writer = null;
    private ?string $serverName = null;
    private bool $serverNameLookedUp = false;
    private ?string $serverVersion = null;
    private bool $serverVersionLookedUp = false;
    private ?string $vendorName = null;
    private ?string $vendorVersion = null;
    private ?Transaction $currentTransaction = null;
    private ?ErrorConverter $errorConverter = null;
    private ?SchemaManager $schemaManager = null;
    private ?string $currentDatabase = null;
    private ?string $currentSchema = null;

    public function __construct(?ConverterPluginRegistry $converterPluginRegistry = null, ?string $currentDatabase = null)
    {
        $this->converterPluginRegistry = $converterPluginRegistry;
        $this->currentDatabase = $currentDatabase;
    }

    /**
     * Disable error converter. Must be called prior to initilization.
     */
    public function disableErrorConverter(): void
    {
        if ($this->errorConverter) {
            throw new QueryBuilderError("Bridge is already initialized, configuration must happend before it gets used.");
        }
        $this->errorConverter = new PassthroughErrorConverter();
    }

    /**
     * @internal
     *   For dependency injection only.
     */
    public function setConverterPluginRegistry(ConverterPluginRegistry $converterPluginRegistry): void
    {
        $this->converterPluginRegistry = $converterPluginRegistry;
    }

    /**
     * Get converter plugin registry.
     */
    public function getConverterPluginRegistry(): ConverterPluginRegistry
    {
        return $this->converterPluginRegistry ??= new ConverterPluginRegistry();
    }

    /**
     * Set server information (avoids lookup).
     */
    public function setServerInfo(?string $serverName = null, ?string $serverVersion = null): void
    {
        $this->serverNameLookedUp = (null !== ($this->serverName = $serverName));
        $this->serverVersionLookedUp = (null !== ($this->serverVersion = $serverVersion));
    }

    /**
     * Please override.
     */
    protected function lookupServerName(): ?string
    {
        return null;
    }

    #[\Override]
    public function getServerName(): ?string
    {
        if ($this->serverNameLookedUp) {
            return $this->serverName;
        }

        $this->serverNameLookedUp = true;

        return $this->serverName = $this->lookupServerName();
    }

    /**
     * Please override.
     */
    protected function lookupServerVersion(): ?string
    {
        return null;
    }

    #[\Override]
    public function getServerVersion(): ?string
    {
        if ($this->serverVersionLookedUp) {
            return $this->serverVersion;
        }

        $this->serverVersionLookedUp = true;

        return $this->serverVersion = $this->lookupServerVersion();
    }

    #[\Override]
    public function getVendorName(): string
    {
        if (null !== $this->vendorName) {
            return $this->vendorName;
        }

        $serverName = $this->getServerName();

        if (null === $serverName) {
            return $this->vendorName = Vendor::UNKNOWN;
        }

        $serverName = Vendor::vendorNameNormalize($serverName);

        if (\str_contains($serverName, 'pg') || \str_contains($serverName, 'postgres')) {
            return $this->vendorName = Vendor::POSTGRESQL;
        }
        if (\str_contains($serverName, 'maria')) {
            return $this->vendorName = Vendor::MARIADB;
        }
        if (\str_contains($serverName, 'my')) {
            return $this->vendorName = Vendor::MYSQL;
        }
        if (\str_contains($serverName, 'sqlite')) {
            return $this->vendorName = Vendor::SQLITE;
        }
        if (\str_contains($serverName, 'sqlsrv') || \str_contains($serverName, 'sqlserver')) {
            return $this->vendorName = Vendor::SQLSERVER;
        }

        return $this->vendorName = $serverName;
    }

    #[\Override]
    public function getVendorVersion(): string
    {
        if (null !== $this->vendorVersion) {
            return $this->vendorVersion;
        }

        $serverVersion = $this->getServerVersion();

        $matches = [];
        if ($serverVersion && \preg_match('/(\d+\.|)\d+\.\d+/ims', $serverVersion, $matches)) {
            $serverVersion = $matches[0];
        }

        return $this->serverVersion = $serverVersion ?? '0.0.0';
    }

    #[\Override]
    public function vendorIs(string|array $name, ?string $version = null, string $operator = '>='): bool
    {
        if (null !== $version && !$this->vendorVersionIs($version, $operator)) {
            return false;
        }

        $vendorName = $this->getVendorName();
        foreach ((array) $name as $candidate) {
            if (Vendor::vendorNameNormalize($candidate) === $vendorName) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function vendorVersionIs(string $version, string $operator = '>='): bool
    {
        return Vendor::versionCompare($version, $this->getVendorVersion(), $operator);
    }

    /** @deprecated */
    #[\Override]
    public function getServerFlavor(): ?string
    {
        return Vendor::UNKNOWN !== ($value = $this->getVendorName()) ? $value : null;
    }

    /** @deprecated */
    #[\Override]
    public function isVersionLessThan(string $version): bool
    {
        return $this->vendorVersionIs($version, '<');
    }

    /** @deprecated */
    #[\Override]
    public function isVersionGreaterOrEqualThan(string $version): bool
    {
        return $this->vendorVersionIs($version, '>=');
    }

    #[\Override]
    public function getCurrentDatabase(): string
    {
        return $this->currentDatabase ??= (string) $this->raw('SELECT ?', [new CurrentDatabase()])->executeQuery()->fetchOne();
    }

    #[\Override]
    public function getDefaultSchema(): string
    {
        return $this->currentSchema ??= (string) $this->raw('SELECT ?', [new CurrentSchema()])->executeQuery()->fetchOne();
    }

    /**
     * Alias of QueryBuilder::raw() which executes the query.
     */
    abstract protected function doExecuteQuery(string $expression, array $arguments = []): Result;

    #[\Override]
    public function executeQuery(string|Expression $expression = null, mixed $arguments = null): Result
    {
        if (\is_string($expression)) {
            $expression = new Raw($expression, $arguments);
        } else if ($arguments) {
            throw new QueryBuilderError("Cannot pass \$arguments if \$query is not a string.");
        }

        $prepared = $this->getWriter()->prepare($expression);

        try {
            return $this->doExecuteQuery($prepared->toString(), $prepared->getArguments()->getAll());
        } catch (\Throwable $e) {
            throw $this->getErrorConverter()->convertError($e, $prepared->toString());
        }
    }

    /**
     * Alias of QueryBuilder::raw() which executes the query.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    abstract protected function doExecuteStatement(string $expression, array $arguments = []): ?int;

    #[\Override]
    public function executeStatement(string|Expression $expression = null, mixed $arguments = null): ?int
    {
        if (\is_string($expression)) {
            $expression = new Raw($expression, $arguments);
        } else if ($arguments) {
            throw new QueryBuilderError("Cannot pass \$arguments if \$query is not a string.");
        }

        $prepared = $this->getWriter()->prepare($expression);

        try {
            return $this->doExecuteStatement($prepared->toString(), $prepared->getArguments()->getAll());
        } catch (\Throwable $e) {
            throw $this->getErrorConverter()->convertError($e, $prepared->toString());
        }
    }

    /**
     * Get current transaction if any.
     */
    private function findCurrentTransaction(): ?Transaction
    {
        if ($this->currentTransaction) {
            if ($this->currentTransaction->isStarted()) {
                return $this->currentTransaction;
            }
            // Transparently cleanup leftovers.
            unset($this->currentTransaction);
        }
        return null;
    }

    /**
     * Create a transaction instance, do not start it.
     */
    protected function doCreateTransaction(int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return match ($this->getVendorName()) {
            Vendor::MARIADB => new MySQLTransaction($this, $isolationLevel),
            Vendor::MYSQL => new MySQLTransaction($this, $isolationLevel),
            Vendor::POSTGRESQL => new PostgreSQLTransaction($this, $isolationLevel),
            Vendor::SQLITE => new SQLiteTransaction($this, $isolationLevel),
            Vendor::SQLSERVER => new SQLServerTransaction($this, $isolationLevel),
            default => throw new QueryBuilderError(\sprintf("Transactions are not supported yet for vendor '%s'", $this->getVendorName())),
        };
    }

    #[\Override]
    public function createTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        $transaction = $this->findCurrentTransaction();

        if ($transaction) {
            if (!$allowPending) {
                throw new TransactionError("a transaction already been started, you cannot nest transactions");
            }
            /* if (!$platform->supportsTransactionSavepoints()) {
                throw new TransactionError("Cannot create a nested transaction, driver does not support savepoints");
            } */

            $savepoint = $transaction->savepoint();

            return $savepoint;
        }

        return $this->currentTransaction = $this->doCreateTransaction($isolationLevel);
    }

    #[\Override]
    public function beginTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        return $this->createTransaction($isolationLevel, $allowPending)->start();
    }

    /**
     * Get error converter.
     */
    protected function getErrorConverter(): ErrorConverter
    {
        return $this->errorConverter ??= $this->createErrorConverter();
    }

    /**
     * @internal
     *    For dependency injection only.
     */
    public function setErrorConverter(ErrorConverter $errorConverter): void
    {
        $this->errorConverter = $errorConverter;
    }

    /**
     * Please override.
     */
    protected function createErrorConverter(): ErrorConverter
    {
        return new PassthroughErrorConverter();
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function createConverter(): Converter
    {
        $ret = match ($this->getVendorName()) {
            Vendor::MARIADB => new MySQLConverter(),
            Vendor::MYSQL => new MySQLConverter(),
            default => new Converter(),
        };

        $ret->setConverterPluginRegistry($this->getConverterPluginRegistry());

        return $ret;
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function getConverter(): Converter
    {
        return $this->converter ??= $this->createConverter();
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function createEscaper(): Escaper
    {
        return match ($this->getVendorName()) {
            Vendor::MARIADB => new MySQLEscaper(),
            Vendor::MYSQL => new MySQLEscaper(),
            default => new StandardEscaper(),
        };
    }

    /**
     * Create default writer based upon server name and version.
     */
    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        $vendorName = $this->getVendorName();

        if (Vendor::POSTGRESQL === $vendorName) {
            return new PostgreSQLWriter($escaper, $converter);
        }

        if (Vendor::MYSQL === $vendorName) {
            if (($serverVersion = $this->getVendorVersion()) && 0 < \version_compare('8.0', $serverVersion)) {
                return new MySQLWriter($escaper, $converter);
            }
            return new MySQL8Writer($escaper, $converter);
        }

        if (Vendor::MARIADB === $vendorName) {
            return new MariaDBWriter($escaper, $converter);
        }

        if (Vendor::SQLITE === $vendorName) {
            return new SQLiteWriter($escaper, $converter);
        }

        if (Vendor::SQLSERVER === $vendorName) {
            return new SQLServerWriter($escaper, $converter);
        }

        return new Writer($escaper, $converter);
    }

    #[\Override]
    public function getWriter(): Writer
    {
        return $this->writer ??= $this->createWriter($this->createEscaper(), $this->getConverter());
    }

    /**
     * Create default schema manager based upon server name and version.
     */
    protected function createSchemaManager(): SchemaManager
    {
        $vendorName = $this->getVendorName();

        if (Vendor::POSTGRESQL === $vendorName) {
            return new PostgreSQLSchemaManager($this);
        }

        if (Vendor::MYSQL === $vendorName) {
            return new MySQLSchemaManager($this);
        }

        if (Vendor::MARIADB === $vendorName) {
            return new MySQLSchemaManager($this);
        }

        if (Vendor::SQLITE === $vendorName) {
            return new SQLiteSchemaManager($this);
        }

        if (Vendor::SQLSERVER === $vendorName) {
            return new SQLServerSchemaManager($this);
        }

        throw new UnsupportedFeatureError(\sprintf("Schema manager is not implemented yet for vendor '%s'", $vendorName));
    }

    #[\Override]
    public function getSchemaManager(): SchemaManager
    {
        return $this->schemaManager ??= $this->createSchemaManager();
    }
}
