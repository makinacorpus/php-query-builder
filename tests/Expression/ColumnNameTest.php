<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ColumnNameTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new ColumnName('foo');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new ColumnName('foo');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithoutNamespace(): void
    {
        $expression = new ColumnName('foo');

        self::assertSame('foo', $expression->getName());
        self::assertNull($expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "foo"
            SQL,
            $expression
        );
    }

    public function testWithNamespace(): void
    {
        $expression = new ColumnName('bar', 'baz');

        self::assertSame('bar', $expression->getName());
        self::assertSame('baz', $expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "baz"."bar"
            SQL,
            $expression
        );
    }

    public function testWildcard(): void
    {
        $expression = new ColumnName('*', 'fizz');

        self::assertSame('*', $expression->getName());
        self::assertSame('fizz', $expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "fizz".*
            SQL,
            $expression
        );
    }
}
