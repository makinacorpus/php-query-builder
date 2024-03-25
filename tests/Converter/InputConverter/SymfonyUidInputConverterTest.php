<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\SymfonyUidInputConverter;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class SymfonyUidInputConverterTest extends UnitTestCase
{
    public function testGuessInputType(): void
    {
        $instance = new SymfonyUidInputConverter();

        self::assertNull($instance->guessInputType(new \DateTimeImmutable()));
        self::assertSameType('uuid', $instance->guessInputType(Uuid::fromString('33881c8a-bfa7-4691-96b7-bcfd03afa115')));
        self::assertSameType('ulid', $instance->guessInputType(Ulid::fromString('01HF9K9FW24BH2F599YYNCHGJF')));
    }

    public function testToSqlUuid(): void
    {
        $uuid = Uuid::fromString('33881c8a-bfa7-4691-96b7-bcfd03afa115');

        $instance = new SymfonyUidInputConverter();

        self::assertSame(
            '33881c8a-bfa7-4691-96b7-bcfd03afa115',
            $instance->toSql(Type::uuid(), $uuid, self::context()),
        );
    }

    public function testToSqlUlid(): void
    {
        $ulid = Ulid::fromString('01HF9K9FW24BH2F599YYNCHGJF');

        $instance = new SymfonyUidInputConverter();

        self::assertSame(
            '01HF9K9FW24BH2F599YYNCHGJF',
            $instance->toSql(Type::raw('ulid'), $ulid, self::context()),
        );
    }

    public function testToSqlWrongValueRaiseError(): void
    {
        $instance = new SymfonyUidInputConverter();

        self::expectException(UnexpectedInputValueTypeError::class);
        $instance->toSql(Type::uuid(), new \DateTimeImmutable(), self::context());
    }
}
