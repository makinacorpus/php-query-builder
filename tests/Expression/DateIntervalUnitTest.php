<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

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
class DateIntervalUnitTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new DateIntervalUnit(3, 'second');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new DateIntervalUnit(2, 'minute');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }
}
