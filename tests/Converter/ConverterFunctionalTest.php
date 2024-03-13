<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ConverterFunctionalTest extends UnitTestCase
{
    public function testPlaceholderEscaper(): void
    {
        self::assertSameSql(
            <<<SQL
            select
                "some_table"."some_column" as "some.alias"
            from "some_schema"."some_table"
            where
                "other_column" = array[#1, #2, #3]
            SQL,
            new Raw(
                <<<SQL
                select
                    ?::column as ?::id
                from ?::table
                where
                    ?::column = ?::array
                SQL,
                [
                    'some_table.some_column',
                    'some.alias',
                    'some_schema.some_table',
                    "other_column",
                    [1, 2, 3],
                ],
            ),
        );
    }
}
