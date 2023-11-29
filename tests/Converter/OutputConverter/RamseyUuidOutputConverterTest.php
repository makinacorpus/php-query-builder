<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\OutputConverter\RamseyUuidOutputConverter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RamseyUuidOutputConverterTest extends UnitTestCase
{
    public function testFromSqlWithUuidClass(): void
    {
        $instance = new RamseyUuidOutputConverter();

        self::assertSame(
            'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a',
            (string) $instance->fromSql(Uuid::class, 'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a', self::context()),
        );
    }

    public function testFromSqlWithUuidInterface(): void
    {
        $instance = new RamseyUuidOutputConverter();

        self::assertSame(
            'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a',
            (string) $instance->fromSql(UuidInterface::class, 'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a', self::context()),
        );
    }
}
