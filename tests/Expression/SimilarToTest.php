<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\SimilarTo;
use MakinaCorpus\QueryBuilder\Expression\SimilarToPattern;
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

    public function testSimilarTo(): void
    {
        $expression = new SimilarTo('foo.bar', 'test%');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to 'test%'
            SQL,
            $expression
        );
    }

    public function testSimilartoWithRightExpression(): void
    {
        $expression = new SimilarTo('foo.bar', new Raw('foo()'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to foo()
            SQL,
            $expression
        );
    }

    public function testSimilarToWithReplacement(): void
    {
        $expression = new SimilarTo('foo.bar', new SimilarToPattern('REPLACE%', 'e-sc_a#p?ed%', 'REPLACE'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to 'e-sc\_a#p\?ed\%%'
            SQL,
            $expression
        );
    }
}
