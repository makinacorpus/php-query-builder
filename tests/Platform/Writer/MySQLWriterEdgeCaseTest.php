<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class MySQLWriterEdgeCaseTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQLWriter(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testRandom(): void
    {
        self::assertSameSql(
            <<<SQL
            rand()
            SQL,
            new Random(),
        );
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

    public function testCastText(): void
    {
        self::assertSameSql(
            <<<SQL
            cast(foo() as char)
            SQL,
            new Cast(new Raw('foo()'), 'varchar'),
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
