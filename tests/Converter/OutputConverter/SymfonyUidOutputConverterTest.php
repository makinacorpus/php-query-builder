<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\OutputConverter\SymfonyUidOutputConverter;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class SymfonyUidOutputConverterTest extends UnitTestCase
{
    public function testFromSqlWithUuid(): void
    {
        $instance = new SymfonyUidOutputConverter();

        self::assertSame(
            'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a',
            (string) $instance->fromSql(Uuid::class, 'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a', self::context()),
        );
    }

    public function testFromSqlWithUuid4(): void
    {
        $instance = new SymfonyUidOutputConverter();

        self::assertSame(
            'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a',
            (string) $instance->fromSql(Uuid::class, 'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a', self::context()),
        );
    }

    public function testFromSqlWithUlid(): void
    {
        $instance = new SymfonyUidOutputConverter();

        self::assertSame(
            '78VESFQ1GN8ZBVWXEBVH13QZ4T',
            (string) $instance->fromSql(Ulid::class, 'e8dbb2fb-8615-47d7-be75-cbdc423bfc9a', self::context()),
        );
    }
}
