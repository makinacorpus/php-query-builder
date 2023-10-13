<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\SimilarTo;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class SimilarToTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new SimilarTo(new Raw('dog'), 'foo%');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new SimilarTo(new Raw('dog'), 'foo%');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testLike(): void
    {
        $expression = new SimilarTo('foo.bar', 'test%');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like 'test%'
            SQL,
            $expression
        );
    }

    public function testLikeCaseInsensitive(): void
    {
        $expression = new SimilarTo('foo.bar', 'test%', null, null, false, true);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" ilike 'test%'
            SQL,
            $expression
        );
    }

    public function testLikeWithReplacement(): void
    {
        $expression = new SimilarTo('foo.bar', '?%', 'e-sc_a#p?ed%', '?');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like 'e-sc\_a#p?ed\%%'
            SQL,
            $expression
        );
    }

    public function testSimilarTo(): void
    {
        $expression = new SimilarTo('foo.bar', 'test%', null, null, true);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to 'test%'
            SQL,
            $expression
        );
    }

    public function testSimilarToWithReplacement(): void
    {
        $expression = new SimilarTo('foo.bar', 'REPLACE%', 'e-sc_a#p?ed%', 'REPLACE', true);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to 'e-sc\_a#p\?ed\%%'
            SQL,
            $expression
        );
    }
}
