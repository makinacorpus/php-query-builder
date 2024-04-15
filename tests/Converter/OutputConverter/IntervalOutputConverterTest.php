<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\OutputConverter\IntervalOutputConverter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\IntervalInputConverter;

class IntervalOutputConverterTest extends UnitTestCase
{
    public static function getValidFromSQLData()
    {
        return [
            ['1 year 2 days 00:01:00', 'P1Y2DT1M'],
            ['P1Y2DT1M', 'P1Y2DT1M'],
            ['2 hour 1 minute 30 second', 'PT2H1M30S'],
            ['02:01:30', 'PT2H1M30S'],
            ['PT2H1M30S', 'PT2H1M30S'],
        ];
    }

    /**
     * @dataProvider getValidFromSQLData
     */
    public function testFromSQL(string $value, string $expected): void
    {
        $instance = new IntervalOutputConverter();

        $extracted = $instance->fromSQL(\DateInterval::class, $value, self::context());

        self::assertInstanceOf(\DateInterval::class, $extracted);
        self::assertSame($expected, IntervalInputConverter::intervalToIso8601($extracted));
    }
}
