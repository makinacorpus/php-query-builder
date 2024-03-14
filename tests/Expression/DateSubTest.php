<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\DateSub;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class DateSubTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new DateSub(new \DateTimeImmutable(), ['month' => 12]);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new DateSub(new \DateTimeImmutable(), ['month' => 12]);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithDateAndString(): void
    {
        $expression = new DateSub(new \DateTimeImmutable(), 'PT1S');

        self::assertSameSql(
            "cast(#1 as timestamp) - cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithDateAndMultipleUnits(): void
    {
        $expression = new DateSub(new \DateTimeImmutable(), 'PT2M1S');

        self::assertSameSql(
            "cast(#1 as timestamp) - cast(#2 || ' ' || #3 || ' ' || #4 || ' ' || #5 as interval)",
            $expression
        );
    }

    public function testWithDateAndInterval(): void
    {
        $expression = new DateSub(new \DateTimeImmutable(), new \DateInterval('PT1S'));

        self::assertSameSql(
            "cast(#1 as timestamp) - cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithDateCastDateIfNotDate(): void
    {
        $expression = new DateSub(new Value('some_column', 'not_a_date'), new \DateInterval('PT1S'));

        self::assertSameSql(
            "cast(#1 as timestamp) - cast(#2 || ' ' || #3 as interval)",
            $expression
        );
    }

    public function testWithStringAndExpression(): void
    {
        $expression = new DateSub('', new Raw('bla'));

        self::assertSameSql(
            'cast(#1 as timestamp) - bla',
            $expression
        );
    }

    public function testWithExpressionAndExpression(): void
    {
        $expression = new DateSub(new Raw('bouh'), new Raw('bla'));

        self::assertSameSql(
            'bouh - bla',
            $expression
        );
    }

    public function testWithInvalidDateStringRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/DateTimeInterface instance or parsable date string/');
        new DateSub('this an unparsable date', new \DateInterval('PT1S'));
    }

    public function testWithInvalidDateTypeRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/DateTimeInterface instance or parsable date string/');
        new DateSub([], new \DateInterval('PT1S'));
    }

    public function testWithInvalidIntervalRaiseError(): void
    {
        self::expectExceptionMessageMatches('/DateInterval instance or an array/');
        new DateSub(new \DateTimeImmutable(), new \DateTime());
    }
}
