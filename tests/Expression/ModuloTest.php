<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Modulo;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ModuloTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Modulo(new Value('12'), new Value('7'));

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Modulo(new Value('12'), new Value('7'));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testModulo(): void
    {
        $expression = new Modulo(new Value('12'), new Value('7'));

        $this->assertSameSql(
            <<<SQL
            cast(#1 as int) % #2
            SQL,
            $expression
        );
    }
}
