<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\DateInputConverter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class DateInputConverterTest extends UnitTestCase
{
    public function testGuessInputType(): void
    {
        $instance = new DateInputConverter();

        self::assertNull($instance->guessInputType(new \SplObjectStorage()));
        self::assertSame('timestampz', $instance->guessInputType(new \DateTimeImmutable()));
    }

    public function testToSqlDateTimeWithTz(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateInputConverter();

        self::assertSame(
            $instance->toSQL('timestamp with time zone', $date, $context),
            '2020-11-27 11:42:34.000000'
        );
    }

    public function testToSqlDateTime(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateInputConverter();

        self::assertSame(
            $instance->toSQL('timestamp', $date, $context),
            '2020-11-27 11:42:34.000000'
        );
    }

    public function testToSqlTimeWithTz(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateInputConverter();

        self::assertSame(
            $instance->toSQL('time with time zone', $date, $context),
            '11:42:34.000000'
        );
    }

    public function testToSqlTime(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateInputConverter();

        self::assertSame(
            $instance->toSQL('time', $date, $context),
            '11:42:34.000000'
        );
    }
}
