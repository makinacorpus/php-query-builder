<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Tests\Query\InsertTest;

class MySQLWriterInsertTest extends InsertTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQLWriter(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testInsertDefaultValues(): void
    {
        $insert = new Insert('some_table');
        $insert->values([]);

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
                ()
            values
                ()
            SQL,
            $insert
        );
    }
}
