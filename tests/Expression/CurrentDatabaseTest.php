<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class CurrentDatabaseTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new CurrentDatabase();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new CurrentDatabase();
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new CurrentDatabase();

        self::assertSameSql(
            <<<SQL
            current_database()
            SQL,
            $expression
        );
    }
}
