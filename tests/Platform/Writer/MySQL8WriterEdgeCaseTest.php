<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;

class MySQL8WriterEdgeCaseTest extends MySQLWriterEdgeCaseTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQL8Writer(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testCastInt(): void
    {
        self::assertSameSql(
            <<<SQL
            cast(foo() as signed)
            SQL,
            new Cast(new Raw('foo()'), 'int4'),
        );
    }

    public function testCastAnythingArray(): void
    {
        self::assertSameSql(
            <<<SQL
            cast(foo() as signed array)
            SQL,
            new Cast(new Raw('foo()'), 'int[]'),
        );
    }
}
