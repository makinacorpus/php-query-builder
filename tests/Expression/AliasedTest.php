<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class AliasedTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Aliased(new Raw('foo'), 'bar');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Aliased(new Raw('foo'), 'bar');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithAlias(): void
    {
        $expression = new Aliased(new Raw('foo'), 'alias');

        self::assertSameSql(
            <<<SQL
            foo as "alias"
            SQL,
            $expression
        );
    }

    public function testWithAliasAndParenthesis(): void
    {
        self::markTestIncomplete('There is no parenthesis friendly expressions yet');

        /*
        $expression = new Aliased(new Raw('foo'), 'alias');

        self::assertSameSql(
            <<<SQL
            (foo) as "alias"
            SQL,
            $expression
        );
         */
    }

    public function testWithoutAlias(): void
    {
        $expression = new Aliased(new Raw('foo'));

        self::assertSameSql(
            <<<SQL
            foo
            SQL,
            $expression
        );
    }
}
