<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
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
        $expression = new DateInterval([DateInterval::UNIT_MILLENIUM => 2]);

        self::assertSame(
            [
                DateInterval::UNIT_YEAR => 2000,
            ],
            $expression->getValues(),
        );
    }

    public function testWithCentury(): void
    {
        $expression = new DateInterval([DateInterval::UNIT_CENTURY => 3, DateInterval::UNIT_YEAR => 1]);

        self::assertSame(
            [
                DateInterval::UNIT_YEAR => 301,
            ],
            $expression->getValues(),
        );
    }

    public function testWithDecade(): void
    {
        $expression = new DateInterval([DateInterval::UNIT_DECADE => 7]);

        self::assertSame(
            [
                DateInterval::UNIT_YEAR => 70,
            ],
            $expression->getValues(),
        );
    }

    public function testWithQuarter(): void
    {
        $expression = new DateInterval([DateInterval::UNIT_QUARTER => 2, DateInterval::UNIT_MONTH => 2]);

        self::assertSame(
            [
                DateInterval::UNIT_MONTH => 8,
            ],
            $expression->getValues(),
        );
    }

    public function testWithWeek(): void
    {
        $expression = new DateInterval([DateInterval::UNIT_WEEK => 3]);

        self::assertSame(
            [
                DateInterval::UNIT_DAY => 21,
            ],
            $expression->getValues(),
        );
    }

    public function testWithArbitraryUnit(): void
    {
        $expression = new DateInterval(['beeeh' => 7]);

        self::assertSame(
            [
                'beeeh' => 7,
            ],
            $expression->getValues(),
        );
    }

    public function testWithDateDateInterval(): void
    {
        $expression = new DateInterval(new \DateInterval('PT1M2S'));

        self::assertSame(
            [
                DateInterval::UNIT_MINUTE => 1,
                DateInterval::UNIT_SECOND => 2,
            ],
            $expression->getValues(),
        );
    }

    public function testWithArrayNotIntegerRaiseError(): void
    {
        self::expectException(QueryBuilderError::class);
        new DateInterval([DateInterval::UNIT_WEEK => 'this is not an int']);
    }
}
