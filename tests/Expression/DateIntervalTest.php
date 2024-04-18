<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
use MakinaCorpus\QueryBuilder\Expression\DateIntervalUnit;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

/**
 * There is no SQL formatting tests here, only unit tests of the class itself.
 *
 * DateInterval are specific to all dialects, even thought the only one that seem
 * to care about SQL standard is PostgreSQL, it doesn't worth testing it as
 * standard SQL.
 *
 * @see DateAddTest
 * @see DateSubTest
 */
class DateIntervalTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new DateInterval(['second' => 1]);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new DateInterval(['minute' => 2]);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithMillenium(): void
    {
        $expression = new DateInterval([DateIntervalUnit::MILLENIUM => 2]);

        self::assertSame(
            [
                DateIntervalUnit::YEAR => 2000,
            ],
            $expression->toArray(),
        );
    }

    public function testWithCentury(): void
    {
        $expression = new DateInterval([DateIntervalUnit::CENTURY => 3, DateIntervalUnit::YEAR => 1]);

        self::assertSame(
            [
                DateIntervalUnit::YEAR => 301,
            ],
            $expression->toArray(),
        );
    }

    public function testWithDecade(): void
    {
        $expression = new DateInterval([DateIntervalUnit::DECADE => 7]);

        self::assertSame(
            [
                DateIntervalUnit::YEAR => 70,
            ],
            $expression->toArray(),
        );
    }

    public function testWithQuarter(): void
    {
        $expression = new DateInterval([DateIntervalUnit::QUARTER => 2, DateIntervalUnit::MONTH => 2]);

        self::assertSame(
            [
                DateIntervalUnit::MONTH => 8,
            ],
            $expression->toArray(),
        );
    }

    public function testWithWeek(): void
    {
        $expression = new DateInterval([DateIntervalUnit::WEEK => 3]);

        self::assertSame(
            [
                DateIntervalUnit::DAY => 21,
            ],
            $expression->toArray(),
        );
    }

    public function testWithArbitraryUnit(): void
    {
        $expression = new DateInterval(['beeeh' => 7]);

        self::assertSame(
            [
                'beeeh' => 7,
            ],
            $expression->toArray(),
        );
    }

    public function testWithDateDateInterval(): void
    {
        $expression = new DateInterval(new \DateInterval('PT1M2S'));

        self::assertSame(
            [
                DateIntervalUnit::MINUTE => 1,
                DateIntervalUnit::SECOND => 2,
            ],
            $expression->toArray(),
        );
    }

    public function testWithDateIsoString(): void
    {
        $expression = new DateInterval('PT1M2S');

        self::assertSame(
            [
                DateIntervalUnit::MINUTE => 1,
                DateIntervalUnit::SECOND => 2,
            ],
            $expression->toArray(),
        );
    }

    public function testWithDateTimeString(): void
    {
        $expression = new DateInterval('1 minute 2 second');

        self::assertSame(
            [
                DateIntervalUnit::MINUTE => 1,
                DateIntervalUnit::SECOND => 2,
            ],
            $expression->toArray(),
        );
    }

    public function testWithArrayNotIntegerRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        // @phpstan-ignore-next-line Error is intentional, this is what we test.
        new DateInterval([DateIntervalUnit::WEEK => 'this is not an int']);
    }
}
