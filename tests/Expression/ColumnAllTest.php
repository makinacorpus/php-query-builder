<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ColumnAll;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ColumnAllTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new ColumnAll('foo');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new ColumnAll('foo');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithNamespace(): void
    {
        $expression = new ColumnAll('foo');

        self::assertSameSql(
            <<<SQL
            "foo".*
            SQL,
            $expression
        );
    }

    public function testWithoutNamespace(): void
    {
        $expression = new ColumnAll();

        self::assertSameSql(
            <<<SQL
            *
            SQL,
            $expression
        );
    }
}
