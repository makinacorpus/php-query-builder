<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class WhereTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Where();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Where();
        $expression->raw('(true or ?)', 'bar');
        $expression->with(new Value(1));

        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Where();
        $expression->raw('(true or ?)', 'bar');
        $expression->with(new Value(1));

        self::assertTrue($expression->returns());

        self::assertSameSql(
            <<<SQL
            (true or #1) and #2
            SQL,
            $expression
        );
    }

    public function testEmpty(): void
    {
        $expression = new Where();

        self::assertSameSql(
            "1",
            $expression
        );
    }

    public function testExpressionWithCallback(): void
    {
        $expression = new Where();
        $expression->with(
            function (Where $where) {
                $where->raw('true');
            },
        );

        self::assertSameSql(
            <<<SQL
            true
            SQL,
            $expression
        );
    }

    public function testNotExpressionInstanceBecomesValue(): void
    {
        $expression = new Where();
        $expression->with('this is an arbitrary user value');

        self::assertSameSql(
            <<<SQL
            #1
            SQL,
            $expression
        );
    }

    public function testNestedCustom(): void
    {
        $expression = new Where('BWA');
        $expression
            ->raw('"foo" is false')
            ->nested('ARG')
                ->raw('"foo" is true')
                ->with('bla')
        ;

        self::assertSameSql(
            <<<SQL
            "foo" is false
            BWA (
                "foo" is true
                ARG
                #1
            )
            SQL,
            $expression
        );
    }

    public function testNestedOr(): void
    {
        $expression = new Where();
        $expression
            ->raw('"foo" is false')
            ->or()
                ->raw('"foo" is true')
                ->with('bla')
        ;

        self::assertSameSql(
            <<<SQL
            "foo" is false
            and (
                "foo" is true
                or
                #1
            )
            SQL,
            $expression
        );
    }

    public function testNestedAnd(): void
    {
        $expression = new Where();
        $expression
            ->raw('"foo" is false')
            ->and()
                ->raw('"foo" is true')
                ->with('bla')
        ;

        self::assertSameSql(
            <<<SQL
            "foo" is false
            and (
                "foo" is true
                and
                #1
            )
            SQL,
            $expression
        );
    }
}
