<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ConverterUnitTest extends UnitTestCase
{
    public function testToExpression(): void
    {
        $converter = new Converter();

        self::assertInstanceOf(ArrayValue::class, $converter->toExpression('foo', 'array'));
        self::assertInstanceOf(ColumnName::class, $converter->toExpression('foo', 'column'));
        self::assertInstanceOf(Identifier::class, $converter->toExpression('foo', 'identifier'));
        self::assertInstanceOf(Row::class, $converter->toExpression('foo', 'row'));
        self::assertInstanceOf(TableName::class, $converter->toExpression('foo', 'table'));
    }

    public function testToExpressionValue(): void
    {
        $converter = new Converter();

        $value = $converter->toExpression('foo', 'value');

        self::assertInstanceOf(Value::class, $value);
        self::assertSame('foo', $value->getValue());
        self::assertNull($value->getType());
    }

    public function testToExpressionUnknownTypeFallback(): void
    {
        $converter = new Converter();

        $value = $converter->toExpression('foo', 'blaa');

        self::assertInstanceOf(Value::class, $value);
        self::assertSame('foo', $value->getValue());
        self::assertSame('blaa', $value->getType());
    }

    public function testToExpressionNullTypeFallback(): void
    {
        $converter = new Converter();

        $value = $converter->toExpression('foo');

        self::assertInstanceOf(Value::class, $value);
        self::assertSame('foo', $value->getValue());
        self::assertNull($value->getType());
    }

    public function testToSqlInt(): void
    {
        self::assertSame(
            12,
            (new Converter())->toSql(12.324234, 'int'),
        );
    }

    public function testToSqlFloat(): void
    {
        self::assertSame(
            13.12,
            (new Converter())->toSql(13.12, 'decimal'),
        );
    }

    public function testToSqlText(): void
    {
        self::assertSame(
            'weee',
            (new Converter())->toSql('weee', 'varchar'),
        );
    }

    public function testToSqlTextStringable(): void
    {
        $object = new class () {
            public function __toString(): string
            {
                return "weee";
            }
        };

        self::assertSame(
            'weee',
            (new Converter())->toSql($object, 'varchar'),
        );
    }

    public function testToSqlGuessTypeInt(): void
    {
        self::assertSame(
            12,
            (new Converter())->toSql(12),
        );
    }

    public function testToSqlGuessTypeFloat(): void
    {
        self::assertSame(
            13.12,
            (new Converter())->toSql(13.12),
        );
    }

    public function testToSqlGuessTypeText(): void
    {
        self::assertSame(
            'weee',
            (new Converter())->toSql('weee'),
        );
    }

    public function testToSqlGuessTypeUnknownFallback(): void
    {
        $object = new \SplObjectStorage();

        self::assertSame(
            $object,
            (new Converter())->toSql($object),
        );
    }
}
