<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Type;

use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testCreate(): void
    {
        $type = Type::create('bigint');
        self::assertSame(InternalType::INT_BIG, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);
    }

    public function testRaw(): void
    {
        $type = Type::raw('bigint');
        self::assertSame(InternalType::UNKNOWN, $type->internal);
        self::assertSame('bigint', $type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);
    }

    public function testCreateWithLength(): void
    {
        $type = Type::create('character varying (12)');
        self::assertSame(InternalType::VARCHAR, $type->internal);
        self::assertNull($type->name);
        self::assertSame(12 , $type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);
    }

    public function testCreateWithPrecisionAndScale(): void
    {
        $type = Type::create('numeric (10,2)');
        self::assertSame(InternalType::DECIMAL, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertSame(10, $type->precision);
        self::assertSame(2, $type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);
    }

    public function testArray(): void
    {
        $type = Type::create('int []');
        self::assertSame(InternalType::INT, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertTrue($type->array);
    }

    public function testCreateWithTimeZone(): void
    {
        $type = Type::create('timestamp with time zone');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertTrue($type->withTimeZone);
        self::assertFalse($type->array);

        $type = Type::create('datetimez');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertTrue($type->withTimeZone);
        self::assertFalse($type->array);
    }

    public function testCreateWithoutTimeZone(): void
    {
        $type = Type::create('timestamp without time zone');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);

        $type = Type::create('datetime');
        self::assertSame(InternalType::TIMESTAMP, $type->internal);
        self::assertNull($type->name);
        self::assertNull($type->length);
        self::assertNull($type->precision);
        self::assertNull($type->scale);
        self::assertFalse($type->withTimeZone);
        self::assertFalse($type->array);
    }
}
