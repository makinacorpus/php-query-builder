<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Driver;

use MakinaCorpus\QueryBuilder\SqlString;
use MakinaCorpus\QueryBuilder\Driver\Driver;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class MockDriver implements Driver
{
    /**
     * Get SQL writer that correspond to the server dialect in use for the
     * current connexion.
     *
     * It is also in charge of creating the Escaper instance, and passing it
     * to the Writer.
     */
    public function getWriter(): Writer
    {
        
    }

    /**
     * Execute given generated SQL and return a result set.
     *
     * Result set type may vary between drivers.
     *
     * It's the driver responsability to convert user given values in the
     * argument bag to acceptable SQL values, most drivers should now how
     * to do this.
     *
     * @todo later in the future, extract SQL value converter from
     *   makinacorpus/goat-query and release it as its own component.
     */
    public function execute(SqlString $query): mixed
    {
        
    }

    /**
     * Execute given generated SQL and return an affected row count.
     *
     * It's the driver responsability to convert user given values in the
     * argument bag to acceptable SQL values, most drivers should now how
     * to do this.
     *
     * @todo later in the future, extract SQL value converter from
     *   makinacorpus/goat-query and release it as its own component.
     */
    public function perform(SqlString $query): int
    {
        
    }
}
