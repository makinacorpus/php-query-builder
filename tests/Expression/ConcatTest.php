<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

class ConcatTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Concat('foo');

        self::assertTrue($expression->returns());
    }

    public function testReturnType(): void
    {
        $expression = new Concat('foo');

        self::assertSameType(Type::text(), $expression->returnType());
    }

    public function testClone(): void
    {
        $expression = new Concat('foo', '12', new Raw('bar()'));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Concat('some_func', '12', new Raw('bar()'));

        self::assertSameSql(
            <<<SQL
            #1 || #2 || bar()
            SQL,
            $expression
        );
    }
}
