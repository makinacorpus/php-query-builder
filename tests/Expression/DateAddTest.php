<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\DateAdd;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class DateAddTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new DateAdd(new \DateTimeImmutable(), ['month' => 12]);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new DateAdd(new \DateTimeImmutable(), ['month' => 12]);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithDateAndString(): void
    {
        $expression = new DateAdd(new \DateTimeImmutable(), 'PT1S');

        self::assertSameSql(
            "cast(#1 as timestamp) + cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithDateAndMultipleUnits(): void
    {
        $expression = new DateAdd(new \DateTimeImmutable(), 'PT2M1S');

        self::assertSameSql(
            "cast(#1 as timestamp) + cast(#2 || ' ' || #3 || ' ' || #4 || ' ' || #5 as interval)",
            $expression
        );
    }

    public function testWithDateAndInterval(): void
    {
        $expression = new DateAdd(new \DateTimeImmutable(), new \DateInterval('PT1S'));

        self::assertSameSql(
            "cast(#1 as timestamp) + cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithDateCastDateIfNotDate(): void
    {
        $expression = new DateAdd(new Value('some_column', 'not_a_date'), new \DateInterval('PT1S'));

        self::assertSameSql(
            "cast(#1 as timestamp) + cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithStringAndExpression(): void
    {
        $expression = new DateAdd('', new Raw('bla'));

        self::assertSameSql(
            'cast(#1 as timestamp) + bla',
            $expression
        );
    }

    public function testWithExpressionAndExpression(): void
    {
        $expression = new DateAdd(new Raw('bouh'), new Raw('bla'));

        self::assertSameSql(
            'bouh + bla',
            $expression
        );
    }

    public function testWithInvalidDateStringRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/DateTimeInterface instance or parsable date string/');
        new DateAdd('this an unparsable date', new \DateInterval('PT1S'));
    }

    public function testWithInvalidDateTypeRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/DateTimeInterface instance or parsable date string/');
        new DateAdd([], new \DateInterval('PT1S'));
    }

    public function testWithInvalidIntervalRaiseError(): void
    {
        self::expectExceptionMessageMatches('/DateInterval instance or an array/');
        new DateAdd(new \DateTimeImmutable(), new \DateTime());
    }
}
