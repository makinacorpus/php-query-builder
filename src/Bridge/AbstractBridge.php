<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\Expression;
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
use MakinaCorpus\QueryBuilder\Writer\Writer;

abstract class AbstractBridge
{
    const SERVER_MARIADB = 'mariadb';
    const SERVER_MYSQL = 'mysql';
    const SERVER_POSTGRESQL = 'postgresql';
    const SERVER_SQLITE = 'sqlite';
    const SERVER_SQLSERVER = 'sqlsrv';

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
    protected function getConverterPluginRegistry(): ConverterPluginRegistry
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
     * Get server name.
     */
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
    protected function lookupServerName(): ?string
    {
        return null;
    }

    public function getServerFlavor(): ?string
    {
        if (null !== $this->serverFlavor) {
            return $this->serverFlavor;
        }

        $serverName = \strtolower($this->getServerName());

        if (\str_contains($serverName, 'pg') || \str_contains($serverName, 'postgres')) {
            return self::SERVER_POSTGRESQL;
        }

        if (\str_contains($serverName, 'maria')) {
            return self::SERVER_MARIADB;
        }

        if (\str_contains($serverName, 'my')) {
            return self::SERVER_MYSQL;
        }

        if (\str_contains($serverName, 'sqlite')) {
            return self::SERVER_SQLITE;
        }

        if (\str_contains($serverName, 'sqlsrv') || \str_contains($serverName, 'sqlserver')) {
            return self::SERVER_SQLSERVER;
        }

        return $this->serverFlavor = $serverName;
    }

    /**
     * Get server version.
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
     * Alias of QueryBuilder::raw() which executes the query.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    protected abstract function doExecuteStatement(string $expression, array $arguments = []): ?int;

    /**
     * Execute query and return affected row count if possible.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
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
    protected function createConverter(ConverterPluginRegistry $converterPluginRegistry): Converter
    {
        $ret = match ($this->getServerFlavor()) {
            self::SERVER_MARIADB => new MySQLConverter(),
            self::SERVER_MYSQL => new MySQLConverter(),
            default => new Converter(),
        };

        $ret->setConverterPluginRegistry($converterPluginRegistry);

        return $ret;
    }

    /**
     * Create default writer based upon server name and version and driver.
     */
    protected function createEscaper(): Escaper
    {
        return match ($this->getServerFlavor()) {
            self::SERVER_MARIADB => new MySQLEscaper(),
            self::SERVER_MYSQL => new MySQLEscaper(),
            default => new StandardEscaper(),
        };
    }

    /**
     * Create default writer based upon server name and version.
     */
    protected function createWriter(Escaper $escaper, Converter $converter): Writer
    {
        $serverFlavor = $this->getServerFlavor();

        if (self::SERVER_POSTGRESQL === $serverFlavor) {
            return new PostgreSQLWriter($escaper, $converter);
        }

        if (self::SERVER_MYSQL === $serverFlavor) {
            if (($serverVersion = $this->getServerVersion()) && 0 < \version_compare('8.0', $serverVersion)) {
                return new MySQLWriter($escaper, $converter);
            }
            return new MySQL8Writer($escaper, $converter);
        }

        if (self::SERVER_MARIADB === $serverFlavor) {
            return new MariaDBWriter($escaper, $converter);
        }

        if (self::SERVER_SQLITE === $serverFlavor) {
            return new SQLiteWriter($escaper, $converter);
        }

        if (self::SERVER_SQLSERVER === $serverFlavor) {
            return new SQLServerWriter($escaper, $converter);
        }

        return new Writer($escaper, $converter);
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter(): Writer
    {
        return $this->writer ??= $this->createWriter(
            $this->createEscaper(),
            $this->createConverter(
                $this->getConverterPluginRegistry(),
            ),
        );
    }
}
