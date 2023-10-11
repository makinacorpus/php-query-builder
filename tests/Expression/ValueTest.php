<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\AbstractWriterTestCase;

class ValueTest extends AbstractWriterTestCase
{
    public function testReturns(): void
    {
        $expression = new Value('bla', 'text');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Value('bla', 'text');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new Value('bla', 'text');

        self::assertTrue($expression->returns());
        self::assertSame('bla', $expression->getValue());
        self::assertSame('text', $expression->getType());

        self::assertSameSql(
            <<<SQL
            #1
            SQL,
            $expression
        );
    }

    public function testWithoutType(): void
    {
        $expression = new Value('foo');

        self::assertTrue($expression->returns());
        self::assertSame('foo', $expression->getValue());
        self::assertNull($expression->getType());

        self::assertSameSql(
            <<<SQL
            #1
            SQL,
            $expression
        );
    }
}
