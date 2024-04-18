<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Writer\Writer;

interface Bridge extends DatabaseSession
{
    /**
     * Disable error converter. Must be called prior to initilization.
     */
    public function disableErrorConverter(): void;

    /**
     * Get server name as exposed by the server itself, not normalized.
     */
    public function getServerName(): ?string;

    /**
     * Get server version as exposed by the server itself, not normalized.
     */
    public function getServerVersion(): ?string;

    /**
     * Get normalized vendor name.
     *
     * @deprecated
     * @see DatabaseSession::getVendorName()
     */
    public function getServerFlavor(): ?string;

    /**
     * Version is less than given.
     *
     * @deprecated
     * @see DatabaseSession::vendorVersionIs()
     */
    public function isVersionLessThan(string $version): bool;

    /**
     * Version is greater or equal than given.
     *
     * @deprecated
     * @see DatabaseSession::vendorVersionIs()
     */
    public function isVersionGreaterOrEqualThan(string $version): bool;

    /**
     * Get SQL writer instance.
     */
    public function getWriter(): Writer;
}
