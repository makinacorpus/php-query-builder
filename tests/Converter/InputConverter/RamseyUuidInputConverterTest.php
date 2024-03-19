<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\RamseyUuidInputConverter;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;
use Ramsey\Uuid\Uuid;

class RamseyUuidInputConverterTest extends UnitTestCase
{
    public function testGuessInputType(): void
    {
        $instance = new RamseyUuidInputConverter();

        self::assertNull($instance->guessInputType(new \DateTimeImmutable()));
        self::assertSameType('uuid', $instance->guessInputType(Uuid::fromString('33881c8a-bfa7-4691-96b7-bcfd03afa115')));
    }

    public function testToSqlUuid(): void
    {
        $uuid = Uuid::fromString('33881c8a-bfa7-4691-96b7-bcfd03afa115');

        $instance = new RamseyUuidInputConverter();

        self::assertSame(
            '33881c8a-bfa7-4691-96b7-bcfd03afa115',
            $instance->toSql(Type::uuid(), $uuid, self::context()),
        );
    }

    public function testToSqlWrongValueRaiseError(): void
    {
        $instance = new RamseyUuidInputConverter();

        self::expectException(UnexpectedInputValueTypeError::class);
        $instance->toSql(Type::uuid(), new \DateTimeImmutable(), self::context());
    }
}
