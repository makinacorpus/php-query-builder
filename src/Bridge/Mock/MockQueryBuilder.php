<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Mock;

use MakinaCorpus\QueryBuilder\Vendor;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Result\ArrayResult;
use MakinaCorpus\QueryBuilder\Result\Result;

/**
 * For testing purpose only.
 */
class MockQueryBuilder extends AbstractBridge
{
    private string $userVendorName;
    private string $userVendorVersion;

    public function __construct(
        ?string $vendorName = null,
        ?string $vendorVersion = null,
    ) {
        parent::__construct();

        $this->userVendorName = $vendorName ?? Vendor::POSTGRESQL;
        $this->userVendorVersion = $vendorVersion ?? '16.0.0';
    }

    #[\Override]
    protected function lookupServerName(): ?string
    {
        return $this->userVendorName;
    }

    #[\Override]
    protected function lookupServerVersion(): ?string
    {
        return $this->userVendorVersion;
    }

    #[\Override]
    protected function doExecuteQuery(string $expression, array $arguments = []): Result
    {
        $result = new ArrayResult([]);
        $result->setConverter($this->getConverter());

        return $result;
    }

    #[\Override]
    protected function doExecuteStatement(string $expression, array $arguments = []): ?int
    {
        return 0;
    }

    #[\Override]
    public function close(): void
    {
    }

    #[\Override]
    public function connect(): void
    {
    }
}
