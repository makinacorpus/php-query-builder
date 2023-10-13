<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Driver;

use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\MySQLEscaper;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\Writer\Writer;

abstract class AbstractDriver implements Driver
{
    private ?Writer $writer = null;
    private ?string $serverName = null;
    private ?string $serverVersion = null;

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
    protected function getServerName(): ?string
    {
        return $this->serverName;
    }

    /**
     * Get server version.
     */
    protected function getServerVersion(): ?string
    {
        return $this->serverVersion;
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
        $serverName = \strtolower($this->getServerName());

        if (\str_contains($serverName, 'pg') || \str_contains($serverName, 'postgres')) {
            return new PostgreSQLWriter($escaper);
        }

        if (\str_contains($serverName, 'mysql')) {
            if (($serverVersion = $this->getServerVersion()) && 0 < \version_compare('8.0', $serverVersion)) {
                return new MySQLWriter($escaper);
            }
            return new MySQL8Writer($escaper);
        }

        if (\str_contains($serverName, 'maria')) {
            return new MySQL8Writer($escaper);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter(): Writer
    {
        return $this->writer ?? ($this->writer = $this->createWriter());
    }
}
