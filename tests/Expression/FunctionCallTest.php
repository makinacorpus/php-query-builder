<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\FunctionCall;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class FunctionCallTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new FunctionCall('foo');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new FunctionCall('foo', '12', new Raw('bar()'));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new FunctionCall('some_func', '12', new Raw('bar()'));

        self::assertSameSql(
            <<<SQL
            "some_func"(#1, bar())
            SQL,
            $expression
        );
    }

    public function testWithNoValues(): void
    {
        $expression = new FunctionCall('now');

        self::assertSameSql(
            <<<SQL
            now()
            SQL,
            $expression
        );
    }
}
