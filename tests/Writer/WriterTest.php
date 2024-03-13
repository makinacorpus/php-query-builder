<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Writer;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class WriterTest extends UnitTestCase
{
    public function testParseExpressionWithType(): void
    {
        $writer = new Writer(new StandardEscaper('#', 1));

        $prepared = $writer->prepare(
            new Raw(
                <<<SQL
                select ?::column
                from ?::table
                where
                    ?::id() = ?::value
                SQL,
                [
                    'the_column',
                    'the_table',
                    'some_function',
                    'some_value',
                ],
            ),
        );

        self::assertSameSql(
            <<<SQL
            select "the_column"
            from "the_table"
            where
                "some_function"() = #1
            SQL,
            $prepared
        );

        self::assertSame(
            [
                'some_value'
            ],
            $prepared->getArguments()->getAll(),
        );
    }
}
