<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\MySQLEscaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\Writer\Writer;

abstract class AbstractBridge
{
    const SERVER_MARIADB = 'mariadb';
    const SERVER_MYSQL = 'mysql';
    const SERVER_POSTGRESQL = 'postgresql';
    const SERVER_SQLITE = 'sqlite';

    private ?Writer $writer = null;
    private ?string $serverName = null;
    private bool $serverNameLookedUp = false;
    private ?string $serverVersion = null;
    private bool $serverVersionLookekUp = false;
    private ?string $serverFlavor = null;

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

        return $this->serverVersion= $this->lookupServerVersion();
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
    protected function createEscaper(): Escaper
    {
        $serverName = \strtolower($this->getServerName());

        if (\str_contains($serverName, 'mysql')) {
            return new MySQLEscaper();
        }

        if (\str_contains($serverName, 'maria')) {
            return new MySQLEscaper();
        }

        return new StandardEscaper();
    }

    /**
     * Create default writer based upon server name and version.
     */
    protected function createWriter(): Writer
    {
        $escaper = $this->createEscaper();
        $serverFlavor = $this->getServerFlavor();

        if (self::SERVER_POSTGRESQL === $serverFlavor) {
            return new PostgreSQLWriter($escaper);
        }

        if (self::SERVER_MYSQL === $serverFlavor) {
            if (($serverVersion = $this->getServerVersion()) && 0 < \version_compare('8.0', $serverVersion)) {
                return new MySQLWriter($escaper);
            }
            return new MySQL8Writer($escaper);
        }

        if (self::SERVER_MARIADB === $serverFlavor) {
            return new MySQL8Writer($escaper);
        }

        return new Writer();
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter(): Writer
    {
        return $this->writer ??= $this->createWriter();
    }
}
