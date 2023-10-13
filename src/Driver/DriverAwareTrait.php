<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Driver;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Writer\Writer;

/**
 * Builds queries, and allow you to forward them to a driver.
 */
trait DriverAwareTrait
{
    private ?Driver $driver = null;
    private ?Writer $writer = null;

    public function __construct(
        ?Driver $driver = null,
        ?Writer $writer = null,
    ) {
        if ($driver) {
            $this->setDriver($driver, false);
        }
        if ($writer) {
            $this->setWriter($writer);
        }
    }

    /**
     * Set the driver for execute() and perform() to work gracefully.
     *
     * Be aware that:
     *   - result set is not typed, because it may vary depending upon driver,
     *   - if no driver is set, those methods will simply raise an exception.
     *
     * @internal
     *   For QueryBuilder usage only.
     */
    public function setDriver(Driver $driver, bool $changeWriter = true): void
    {
        $this->driver = $driver;

        if ($changeWriter) {
            $this->writer = $driver->getWriter();
        }
    }

    /**
     * Get driver.
     */
    protected function getDriver(): Driver
    {
        return $this->driver ?? throw new QueryBuilderError("Driver is not set.");
    }

    /**
     * Set writer if different from the driver one.
     */
    public function setWriter(Writer $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * Get writer.
     */
    protected function getWriter(): Writer
    {
        return $this->writer ?? $this->driver?->getWriter() ?? ($this->writer = new Writer());
    }
}
