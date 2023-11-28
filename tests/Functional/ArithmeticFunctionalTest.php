<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Expression\Modulo;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;

class ArithmeticFunctionalTest extends DoctrineTestCase
{
    public function testModulo(): void
    {
        $select = new Select();
        $select->columnRaw(new Modulo(new Value(10), new Value(7)));

        self::assertSame(
            3,
            (int) $this->executeQuery($select)->fetchOne(),
        );
    }
}
