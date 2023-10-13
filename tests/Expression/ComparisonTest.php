<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Comparison;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ComparisonTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Comparison(new Raw('dog'), new Raw('cat'), 'dont like');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Comparison(new Raw('dog'), new Raw('cat'), 'dont like');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithLeft(): void
    {
        $expression = new Comparison(new Raw('dog'), null, null);

        $this->assertSameSql(
            <<<SQL
            dog
            SQL,
            $expression
        );
    }

    public function testWithLeftOperator(): void
    {
        $expression = new Comparison(new Raw('dog'), null, 'eats');

        $this->assertSameSql(
            <<<SQL
            dog eats
            SQL,
            $expression
        );
    }

    public function testWithRight(): void
    {
        $expression = new Comparison(null, new Raw('cat'), null);

        $this->assertSameSql(
            <<<SQL
            cat
            SQL,
            $expression
        );
    }

    public function testWithRightOperator(): void
    {
        $expression = new Comparison(null, new Raw('cat'), 'eats');

        $this->assertSameSql(
            <<<SQL
            eats cat
            SQL,
            $expression
        );
    }

    public function testWithLeftRight(): void
    {
        $expression = new Comparison(new Raw('dog'), new Raw('cat'), 'eats');

        $this->assertSameSql(
            <<<SQL
            dog eats cat
            SQL,
            $expression
        );
    }
}
