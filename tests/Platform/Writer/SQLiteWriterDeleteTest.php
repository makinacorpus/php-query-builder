<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\SQLiteWriter;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Tests\Query\DeleteTest;

class SQLiteWriterDeleteTest extends DeleteTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new SQLiteWriter(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testClone()
    {
        $query = new Delete('d');
        $query
            ->with('sdf', new ConstantTable([[1, 2]]))
            ->from('a')
            ->where('foo', 42)
        ;

        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testMultipleJoin(): void
    {
        // SQLite does not support JOIN|USING in DELETE queries.
        self::expectNotToPerformAssertions();
    }
}
