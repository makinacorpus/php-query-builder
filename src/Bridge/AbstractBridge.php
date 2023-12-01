<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\DefaultQueryBuilder;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Converter\ConverterPluginRegistry;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Converter\MySQLConverter;
use MakinaCorpus\QueryBuilder\Platform\Escaper\MySQLEscaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MariaDBWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\SQLServerWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\SQLiteWriter;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Writer\Writer;

abstract class AbstractBridge extends DefaultQueryBuilder implements Bridge
{
    private ?Converter $converter = null;
    private ?ConverterPluginRegistry $converterPluginRegistry = null;
    private ?Writer $writer = null;
    private ?string $serverName = null;
    private bool $serverNameLookedUp = false;
    private ?string $serverVersion = null;
    private bool $serverVersionLookekUp = false;
    private ?string $serverFlavor = null;

    public function __construct(?ConverterPluginRegistry $converterPluginRegistry = null)
    {
        $this->converterPluginRegistry = $converterPluginRegistry;
        $this->setQueryExecutor($this);
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
        $this->serverName = $serverName;
        $this->serverVersion = $serverVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerName(): ?string
    {
        if ($this->serverNameLookedUp) {
            return $this->serverName;
        }

        $this->serverName = $this->lookupServerName();
        $this->serverNameLookedUp = true;

        return $this->serverName;
    }

    /**
     * Please override.
     */
    protected function lookupServerName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerFlavor(): ?string
    {
        if (null !== $this->serverFlavor) {
            return $this->serverFlavor;
        }

        $serverName = $this->getServerName();

        if (null === $serverName) {
            return null;
        }

        $serverName = \strtolower($serverName);

        if (\str_contains($serverName, 'pg') || \str_contains($serverName, 'postgres')) {
            return Platform::POSTGRESQL;
        }

        if (\str_contains($serverName, 'maria')) {
            return Platform::MARIADB;
        }

        if (\str_contains($serverName, 'my')) {
            return Platform::MYSQL;
        }

        if (\str_contains($serverName, 'sqlite')) {
            return Platform::SQLITE;
        }

        if (\str_contains($serverName, 'sqlsrv') || \str_contains($serverName, 'sqlserver')) {
            return Platform::SQLSERVER;
        }

        return $this->serverFlavor = $serverName;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion(): ?string
    {
        if ($this->serverVersionLookekUp) {
            return $this->serverVersion;
        }

        $this->serverVersionLookekUp = true;

        $serverVersion = $this->lookupServerVersion();

        $matches = [];
        if ($serverVersion && \preg_match('/(\d+\.|)\d+\.\d+/ims', $serverVersion, $matches)) {
            $serverVersion = $matches[0];
        }

        return $this->serverVersion = $serverVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function isVersionLessThan(string $version): bool
    {
        if (!$serverVersion = $this->getServerVersion()) {
            throw new \Exception(\sprintf("Database '%s', server version is null", $this->getServerFlavor()));
        }

        return 0 > \version_compare(
            Platform::versionNormalize($serverVersion),
            Platform::versionNormalize($version),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isVersionGreaterOrEqualThan(string $version): bool
    {
        if (!$serverVersion = $this->getServerVersion()) {
            throw new \Exception(\sprintf("Database '%s', server version is null", $this->getServerFlavor()));
        }

        return 0 <= \version_compare(
            Platform::versionNormalize($serverVersion),
            Platform::versionNormalize($version),
        );
    }

    /**
     * Alias of QueryBuilder::raw() which executes the query.
     */
    abstract protected function doExecuteQuery(string $expression, array $arguments = []): Result;

    /**
     * {@inheritdoc}
     */
    public function executeQuery(string|Expression $expression = null, mixed $arguments = null): Result
    {
        if (\is_string($expression)) {
            $expression = new Raw($expression, $arguments);
        } else if ($arguments) {
            throw new QueryBuilderError("Cannot pass \$arguments if \$query is not a string.");
        }

        $prepared = $this->getWriter()->prepare($expression);

        return $this->doExecuteQuery($prepared->toString(), $prepared->getArguments()->getAll());
    }

    /**
     * Alias of QueryBuilder::raw() which executes the query.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    abstract protected function doExecuteStatement(string $expression, array $arguments = []): ?int;

    /**
     * {@inheritdoc}
     */
    public function executeStatement(string|Expression $expression = null, mixed $arguments = null): ?int
    {
        if (\is_string($expression)) {
            $expression = new Raw($expression, $arguments);
        } else if ($arguments) {
            throw new QueryBuilderError("Cannot pass \$arguments if \$query is not a string.");
        }

        $prepared = $this->getWriter()->prepare($expression);

        return $this->doExecuteStatement($prepared->toString(), $prepared->getArguments()->getAll());
    }

    /**
     * Please override.
     */
    protected function lookupServerVersion(): ?string
    {
        return null;
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function getConverter(): Converter
    {
        $ret = match ($this->getServerFlavor()) {
            Platform::MARIADB => new MySQLConverter(),
            Platform::MYSQL => new MySQLConverter(),
            default => new Converter(),
        };

        $ret->setConverterPluginRegistry($this->getConverterPluginRegistry());

        return $ret;
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function createEscaper(): Escaper
    {
        return match ($this->getServerFlavor()) {
            Platform::MARIADB => new MySQLEscaper(),
            Platform::MYSQL => new MySQLEscaper(),
            default => new StandardEscaper(),
        };
    }

    /**
     * Create default writer based upon server name and version.
     */
    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        $serverFlavor = $this->getServerFlavor();

        if (Platform::POSTGRESQL === $serverFlavor) {
            return new PostgreSQLWriter($escaper, $converter);
        }

        if (Platform::MYSQL === $serverFlavor) {
            if (($serverVersion = $this->getServerVersion()) && 0 < \version_compare('8.0', $serverVersion)) {
                return new MySQLWriter($escaper, $converter);
            }
            return new MySQL8Writer($escaper, $converter);
        }

        if (Platform::MARIADB === $serverFlavor) {
            return new MariaDBWriter($escaper, $converter);
        }

        if (Platform::SQLITE === $serverFlavor) {
            return new SQLiteWriter($escaper, $converter);
        }

        if (Platform::SQLSERVER === $serverFlavor) {
            return new SQLServerWriter($escaper, $converter);
        }

        return new Writer($escaper, $converter);
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter(): Writer
    {
        return $this->writer ??= $this->createWriter($this->createEscaper(), $this->getConverter());
    }
}
