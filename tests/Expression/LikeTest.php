<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Like;
use MakinaCorpus\QueryBuilder\Expression\LikePattern;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class LikeTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Like(new Raw('dog'), 'foo%');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Like(new Raw('dog'), 'foo%');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testLike(): void
    {
        $expression = new Like('foo.bar', 'test%');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like 'test%'
            SQL,
            $expression
        );
    }

    public function testLikeWithRightExpression(): void
    {
        $expression = new Like('foo.bar', new Raw('foo()'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like foo()
            SQL,
            $expression
        );
    }

    public function testLikeCaseInsensitive(): void
    {
        $expression = new Like('foo.bar', 'test%', false);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" ilike 'test%'
            SQL,
            $expression
        );
    }

    public function testLikeWithReplacement(): void
    {
        $expression = new Like('foo.bar', new LikePattern('?%', 'e-sc_a#p?ed%', '?'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like 'e-sc\_a#p?ed\%%'
            SQL,
            $expression
        );
    }
}
