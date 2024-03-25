<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\DataType;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

class DataTypeTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new DataType('varchar');

        self::assertFalse($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new DataType('varchar');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithUnsigned(): void
    {
        $expression = new DataType(Type::create('unsigned integer'));

        self::assertSameSql(
            <<<SQL
            unsigned int
            SQL,
            $expression,
        );
    }

    public function testWithPrecisionAndScale(): void
    {
        $expression = new DataType(Type::create('decimal (10,2)'));

        self::assertSameSql(
            <<<SQL
            decimal(10,2)
            SQL,
            $expression,
        );
    }

    public function testWithLength(): void
    {
        $expression = new DataType(Type::create('varchar (6)'));

        self::assertSameSql(
            <<<SQL
            varchar(6)
            SQL,
            $expression,
        );
    }

    public function testWithLengthAndArray(): void
    {
        $expression = new DataType(Type::create('varchar(6)[]'));

        self::assertSameSql(
            <<<SQL
            varchar(6)[]
            SQL,
            $expression,
        );
    }
}
