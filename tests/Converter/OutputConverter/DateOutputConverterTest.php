<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\DateInputConverter;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter\DateOutputConverter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class DateOutputConverterTest extends UnitTestCase
{
    public static function dataPhpType()
    {
        return [[\DateTime::class], [\DateTimeImmutable::class], [\DateTimeInterface::class]];
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlDateTimeWithUsecTz(string $phpType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34.901965+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlDateTimeWithTz(string $phpType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.000000+01:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlDateTimeWithUsec(string $phpType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34.901965';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlDateTime(string $phpType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.000000+01:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlTimeWithUsecTz(string $phpType): void
    {
        $sqlDate = '13:42:34.901965+00';

        $context = self::contextWithTimeZone('UTC');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.901965+00:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlTimeWithTz(string $phpType): void
    {
        $sqlDate = '13:42:34+00';

        $context = self::contextWithTimeZone('UTC');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.000000+00:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlTimeWithUsec(string $phpType): void
    {
        $sqlDate = '13:42:34.901965';

        $context = self::contextWithTimeZone('UTC');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.901965+00:00'
        );
    }

    /**
     * @dataProvider dataPhpType()
     */
    public function testFromSqlTime(string $phpType): void
    {
        $sqlDate = '13:42:34';

        $context = self::contextWithTimeZone('UTC');
        $instance = new DateOutputConverter();

        self::assertSame(
            $instance->fromSql($phpType, $sqlDate, $context)->format(DateInputConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.000000+00:00'
        );
    }
}
