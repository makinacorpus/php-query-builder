<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\QueryExecutor;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Writer\Writer;

interface Bridge extends QueryExecutor, QueryBuilder
{
    /**
     * Disable error converter. Must be called prior to initilization.
     */
    public function disableErrorConverter(): void;

    /**
     * Get server name.
     */
    public function getServerName(): ?string;

    /**
     * Get server product type.
     */
    public function getServerFlavor(): ?string;

    /**
     * Get server version.
     */
    public function getServerVersion(): ?string;

    /**
     * Version is less than given.
     */
    public function isVersionLessThan(string $version): bool;

    /**
     * Version is greater or equal than given.
     */
    public function isVersionGreaterOrEqualThan(string $version): bool;

    /**
     * Get SQL writer instance.
     */
    public function getWriter(): Writer;

    /**
     * Get schema manager.
     */
    public function getSchemaManager(): SchemaManager;

    /**
     * Free everything.
     */
    public function close(): void;
}
