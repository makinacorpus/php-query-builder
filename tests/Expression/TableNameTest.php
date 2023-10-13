<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class TableNameTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new TableName('foo', 'bar', 'fizz');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new TableName('foo', 'bar', 'fizz');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithoutNamespace(): void
    {
        $expression = new TableName('foo');

        self::assertTrue($expression->returns());
        self::assertSame('foo', $expression->getName());
        self::assertNull($expression->getAlias());
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
        $expression = new TableName('bar', null, 'baz');

        self::assertTrue($expression->returns());
        self::assertSame('bar', $expression->getName());
        self::assertNull($expression->getAlias());
        self::assertSame('baz', $expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "baz"."bar"
            SQL,
            $expression
        );
    }

    public function testWithAlias(): void
    {
        $expression = new TableName('foo', 'fizz');

        self::assertTrue($expression->returns());
        self::assertSame('foo', $expression->getName());
        self::assertSame('fizz', $expression->getAlias());
        self::assertNull($expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "foo" as "fizz"
            SQL,
            $expression
        );
    }
}
