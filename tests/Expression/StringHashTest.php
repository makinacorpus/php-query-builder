<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\StringHash;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

class StringHashTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new StringHash('foo', 'md5');

        self::assertTrue($expression->returns());
    }

    public function testReturnType(): void
    {
        $expression = new StringHash('foo', 'md5');

        self::assertSameType(Type::text(), $expression->returnType());
    }

    public function testClone(): void
    {
        $expression = new StringHash('foo', 'md5');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testMd5(): void
    {
        $expression = new StringHash('foo', 'md5');

        self::assertSameSql(
            <<<SQL
            md5(cast(#1 as varchar))
            SQL,
            $expression
        );
    }

    public function testCastIfNotTypes(): void
    {
        $expression = new StringHash('foo', 'sha1');

        self::assertSameSql(
            <<<SQL
            digest(cast(#1 as varchar), 'sha1')
            SQL,
            $expression
        );
    }
}
