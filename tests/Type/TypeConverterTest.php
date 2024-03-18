<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Type;

use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use PHPUnit\Framework\TestCase;

class TypeConverterTest extends TestCase
{
    public function testCreate(): void
    {
        $instance = new TypeConverter();

        $type = $instance->create('bigint');
        self::assertSame(InternalType::INT_BIG, $type->internal);
        self::assertSame('bigint', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
    }

    public function testCreateWithLength(): void
    {
        $instance = new TypeConverter();

        $type = $instance->create('character varying (12)');
        self::assertSame(InternalType::VARCHAR, $type->internal);
        self::assertSame('varchar', $type->name);
        self::assertSame(12 , $type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
    }

    public function testCreateWithPrecisionAndScale(): void
    {
        $instance = new TypeConverter();

        $type = $instance->create('numeric (10,2)');
        self::assertSame(InternalType::DECIMAL, $type->internal);
        self::assertSame('decimal', $type->name);
        self::assertNull($type->length);
        self::assertSame(10, $type->precision);
        self::assertSame(2, $type->scale);
        self::assertFalse($type->withTimeZone);
    }

    public function testCreateWithTimeZone(): void
    {
        $instance = new TypeConverter();

        $type = $instance->create('timestamp with time zone');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertSame('timestamp', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertTrue($type->withTimeZone);

        $type = $instance->create('datetimez');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertSame('timestamp', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertTrue($type->withTimeZone);
    }

    public function testCreateWithoutTimeZone(): void
    {
        $instance = new TypeConverter();

        $type = $instance->create('timestamp without time zone');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertSame('timestamp', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);

        $type = $instance->create('datetime');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertSame('timestamp', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
    }
}
