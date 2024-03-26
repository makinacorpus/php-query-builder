<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class CurrentSchemaTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new CurrentSchema();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new CurrentSchema();
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new CurrentSchema();

        self::assertSameSql(
            <<<SQL
            current_schema()
            SQL,
            $expression
        );
    }
}
