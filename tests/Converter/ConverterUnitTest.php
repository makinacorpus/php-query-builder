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
use MakinaCorpus\QueryBuilder\Type\Type;

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
        self::assertSameType(Type::raw('blaa'), $value->getType());
    }

    public function testToExpressionNullTypeFallback(): void
    {
        $converter = new Converter();

        $value = $converter->toExpression('foo');

        self::assertInstanceOf(Value::class, $value);
        self::assertSame('foo', $value->getValue());
        self::assertNull($value->getType());
    }

    public function testFromSqlBool(): void
    {
        $converter = new Converter();

        self::assertFalse($converter->fromSql('bool', "false"));
        self::assertFalse($converter->fromSql('bool', "f"));
        self::assertFalse($converter->fromSql('bool', "FALSE"));
        self::assertFalse($converter->fromSql('bool', "F"));
        self::assertTrue($converter->fromSql('bool', "true"));
        self::assertTrue($converter->fromSql('bool', "t"));
        self::assertTrue($converter->fromSql('bool', "TRUE"));
        self::assertTrue($converter->fromSql('bool', "T"));
    }

    public function testFromSqlBoolAsInt(): void
    {
        $converter = new Converter();

        self::assertFalse($converter->fromSql('bool', 0));
        self::assertTrue($converter->fromSql('bool', 1));
    }

    public function testFromSqlBoolAsIntString(): void
    {
        $converter = new Converter();

        self::assertFalse($converter->fromSql('bool', "0"));
        self::assertTrue($converter->fromSql('bool', "1"));
    }

    public function testFromSqlIntAsString(): void
    {
        $converter = new Converter();

        self::assertSame(12, $converter->fromSql('int', "12"));
        self::assertSame(12, $converter->fromSql('int', "12.2"));
    }

    public function testFromSqlInt(): void
    {
        $converter = new Converter();

        self::assertSame(12, $converter->fromSql('int', 12));
        self::assertSame(12, $converter->fromSql('int', 12.2));
    }

    public function testFromSqlFloat(): void
    {
        $converter = new Converter();

        self::assertSame(12.0, $converter->fromSql('float', "12"));
        self::assertSame(12.2, $converter->fromSql('float', "12.2"));
    }

    public function testFromSqlFloatAsString(): void
    {
        $converter = new Converter();

        self::assertSame(12.0, $converter->fromSql('float', 12));
        self::assertSame(12.2, $converter->fromSql('float', 12.2));
    }

    public function testFromSqlString(): void
    {
        $converter = new Converter();

        self::assertSame("weeeeh", $converter->fromSql('string', "weeeeh"));
    }

    public function testFromSqlPlugin(): void
    {
        $converter = new Converter();

        self::assertInstanceof(\DateTimeImmutable::class, $converter->fromSql(\DateTimeImmutable::class, "2012-12-12 12:12:12"));
    }

    public function testFromSqlArrayEmpty(): void
    {
        $converter = new Converter();

        self::assertSame([], $converter->fromSql('int[]', "{}"));
    }

    public function testFromSqlArray(): void
    {
        $converter = new Converter();

        self::assertSame([1, 7, 11], $converter->fromSql('int[]', "{1,7,11}"));
    }

    public function testFromSqlArrayRecursive(): void
    {
        $converter = new Converter();

        self::assertSame([[1, 2], [3, 4]], $converter->fromSql('int[]', "{{1,2},{3,4}}"));
    }

    public function testFromSqlArrayRows(): void
    {
        self::markTestIncomplete("Not implemented yet.");
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

    public function testToSqlArray(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    public function testToSqlArrayRecursive(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    public function testToSqlArrayRows(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }
}
