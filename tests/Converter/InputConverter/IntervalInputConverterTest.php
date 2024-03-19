<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\IntervalInputConverter;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

final class IntervalInputConverterTest extends UnitTestCase
{
    public function testGuessInputType(): void
    {
        $instance = new IntervalInputConverter();

        self::assertNull($instance->guessInputType(new \DateTimeImmutable()));
        self::assertSameType('interval', $instance->guessInputType(new \DateInterval('P1Y2DT1M')));
    }

    public static function getValidToSQLData()
    {
        return [
            ['P1Y2DT1M', new \DateInterval('P1Y2DT1M')],
            ['PT2H1M30S', new \DateInterval('PT2H1M30S')],
        ];
    }

    /**
     * @dataProvider getValidToSQLData
     */
    public function testValidToSQL($expected, $value): void
    {
        $converter = new IntervalInputConverter();
        $context = self::context();

        // Converter only supports PHP \DateInterval structures as input
        self::assertSame($expected, $converter->toSQL(Type::dateInterval(), $value, $context));
    }

    public static function getInvalidToSQLData()
    {
        return [
            ['aahhh'],
            [12],
            [new \DateTime()],
            [[]],
            ['P1Y2DT1M'],
            [null],
        ];
    }

    /**
     * @dataProvider getInvalidToSQLData
     */
    public function testInvalidToSQL($invalidValue): void
    {
        $converter = new IntervalInputConverter();
        $context = self::context();

        self::expectException(UnexpectedInputValueTypeError::class);
        $converter->toSQL(Type::dateInterval(), $invalidValue, $context);
    }
}
