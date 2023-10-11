<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\ArgumentBag;
use MakinaCorpus\QueryBuilder\SqlString;
use PHPUnit\Framework\TestCase;

class SqlStringTest extends TestCase
{
    public function testEverything(): void
    {
        $sqlString = new SqlString('select 1', null, 'bla');

        self::assertSame('select 1', $sqlString->toString());
        self::assertSame('select 1', (string) $sqlString);
        self::assertSame('bla', $sqlString->getIdentifier());
        self::assertInstanceOf(ArgumentBag::class, $sqlString->getArguments());
    }

    public function testIdentifierIsGeneratedWhenNoneGiven(): void
    {
        $sqlString = new SqlString('select 1');

        self::assertStringStartsWith('mcqb_', $sqlString->getIdentifier());
    }
}
