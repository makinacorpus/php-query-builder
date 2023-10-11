<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Tests\AbstractWriterTestCase;

class IdentifierTest extends AbstractWriterTestCase
{
    public function testReturns(): void
    {
        $expression = new Identifier('bla');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Identifier('bla');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithoutNamespace(): void
    {
        $expression = new Identifier('foo');

        self::assertTrue($expression->returns());
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
        $expression = new Identifier('bar', 'baz');

        self::assertTrue($expression->returns());
        self::assertSame('bar', $expression->getName());
        self::assertSame('baz', $expression->getNamespace());

        self::assertSameSql(
            <<<SQL
            "baz"."bar"
            SQL,
            $expression
        );
    }
}
