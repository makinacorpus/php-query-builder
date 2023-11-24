<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Writer\Writer;

interface Bridge
{
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
     * Execute query and return affected row count if possible.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    public function executeStatement(string|Expression $expression = null, mixed $arguments = null): ?int;

    /**
     * Get SQL writer instance.
     */
    public function getWriter(): Writer;
}
