<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Query\Select;

class WhereBuilderTest extends UnitTestCase
{
    public function testRaw(): void
    {
        $expression = new Where();
        $expression->raw('this is an arbitrary expression ?', ['foo']);

        self::assertSameSql(
            <<<SQL
            this is an arbitrary expression #1
            SQL,
            $expression
        );
    }

    public function testExistsSelect(): void
    {
        self::markTestIncomplete("Not implemented yet");
    }

    public function testExistsRaw(): void
    {
        $expression = new Where();
        $expression->exists(new Raw('true'));

        self::assertSameSql(
            <<<SQL
            exists true
            SQL,
            $expression
        );
    }

    public function testNotExistsSelect(): void
    {
        self::markTestIncomplete("Not implemented yet");
    }

    public function testNotExistsRaw(): void
    {
        $expression = new Where();
        $expression->notExists(new Raw('true'));

        self::assertSameSql(
            <<<SQL
            not exists true
            SQL,
            $expression
        );
    }

    public function testIsEqualExpression(): void
    {
        $expression = new Where();
        $expression->isEqual(new Raw('left'), new Raw('right'));

        self::assertSameSql(
            <<<SQL
            left = right
            SQL,
            $expression
        );
    }

    public function testIsEqualColumn(): void
    {
        $expression = new Where();
        $expression->isEqual('foo.bar', 12);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" = #1
            SQL,
            $expression
        );
    }

    public function testIsNotEqualExpression(): void
    {
        $expression = new Where();
        $expression->isNotEqual(new Raw('left'), new Raw('right'));

        self::assertSameSql(
            <<<SQL
            left <> right
            SQL,
            $expression
        );
    }

    public function testIsNotEqualColumn(): void
    {
        $expression = new Where();
        $expression->isNotEqual('foo.bar', 12);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" <> #1
            SQL,
            $expression
        );
    }

    public function testIsLike(): void
    {
        $expression = new Where();
        $expression->isLike('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsLikeExpression(): void
    {
        $expression = new Where();
        $expression->isLike('foo.bar', new Raw('foo()'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" like foo()
            SQL,
            $expression
        );
    }

    public function testIsNotLike(): void
    {
        $expression = new Where();
        $expression->isNotLike('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            not "foo"."bar" like 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsLikeCaseInsensitive(): void
    {
        $expression = new Where();
        $expression->isLikeInsensitive('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" ilike 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsNotLikeCaseInsensitive(): void
    {
        $expression = new Where();
        $expression->isNotLikeInsensitive('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            not "foo"."bar" ilike 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsSimilarTo(): void
    {
        $expression = new Where();
        $expression->isSimilarTo('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsSimilarToExpression(): void
    {
        $expression = new Where();
        $expression->isSimilarTo('foo.bar', new Raw('foo()'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" similar to foo()
            SQL,
            $expression
        );
    }

    public function testIsNotSimilarTo(): void
    {
        $expression = new Where();
        $expression->isNotSimilarTo('foo.bar', 'cou#%', 'cou', '#');

        self::assertSameSql(
            <<<SQL
            not "foo"."bar" similar to 'coucou%'
            SQL,
            $expression
        );
    }

    public function testIsIn(): void
    {
        $expression = new Where();
        $expression->isIn('foo.bar', ['fizz', 'buzz']);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" in (#1, #2)
            SQL,
            $expression
        );
    }

    public function testIsInRow(): void
    {
        $expression = new Where();
        $expression->isIn('foo.bar', new Row(['fizz', 'buzz']));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" in (#1, #2)
            SQL,
            $expression
        );
    }

    public function testIsInExpression(): void
    {
        $expression = new Where();
        $expression->isIn('foo.bar', (new Select('some_table'))->column('some_column'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" in (select "some_column" from "some_table")
            SQL,
            $expression
        );
    }

    public function testIsNotIn(): void
    {
        $expression = new Where();
        $expression->isNotIn('foo.bar', ['fizz', 'buzz']);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" not in (#1, #2)
            SQL,
            $expression
        );
    }

    public function testIsNotInRow(): void
    {
        $expression = new Where();
        $expression->isNotIn('foo.bar', new Row(['fizz', 'buzz']));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" not in (#1, #2)
            SQL,
            $expression
        );
    }

    public function testIsNotInExpression(): void
    {
        $expression = new Where();
        $expression->isNotIn('foo.bar', (new Select('some_table'))->column('some_column'));

        self::assertSameSql(
            <<<SQL
            "foo"."bar" not in (select "some_column" from "some_table")
            SQL,
            $expression
        );
    }

    public function testIsGreater(): void
    {
        $expression = new Where();
        $expression->isGreater('foo.bar', 1);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" > #1
            SQL,
            $expression
        );
    }

    public function testIsLess(): void
    {
        $expression = new Where();
        $expression->isLess('foo.bar', 1);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" < #1
            SQL,
            $expression
        );
    }

    public function testIsGreaterOrEqual(): void
    {
        $expression = new Where();
        $expression->isGreaterOrEqual('foo.bar', 1);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" >= #1
            SQL,
            $expression
        );
    }

    public function testIsLessOrEqual(): void
    {
        $expression = new Where();
        $expression->isLessOrEqual('foo.bar', 1);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" <= #1
            SQL,
            $expression
        );
    }

    public function testIsBetween(): void
    {
        $expression = new Where();
        $expression->isBetween('foo.bar', 1, 2);

        self::assertSameSql(
            <<<SQL
            "foo"."bar" between #1 and #2
            SQL,
            $expression
        );
    }

    public function testIsNotBetween(): void
    {
        $expression = new Where();
        $expression->isNotBetween('foo.bar', 1, 2);

        self::assertSameSql(
            <<<SQL
            not "foo"."bar" between #1 and #2
            SQL,
            $expression
        );
    }

    public function testIsNull(): void
    {
        $expression = new Where();
        $expression->isNull('foo.bar');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" is null
            SQL,
            $expression
        );
    }

    public function testIsNotNull(): void
    {
        $expression = new Where();
        $expression->isNotNull('foo.bar');

        self::assertSameSql(
            <<<SQL
            "foo"."bar" is not null
            SQL,
            $expression
        );
    }
}
