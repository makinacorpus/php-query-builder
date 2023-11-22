<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Expression\Lpad;

class TextFunctionalTest extends DoctrineTestCase
{
    public function testConcat(): void
    {
        $select = new Select();
        $select->columnRaw(new Concat('foo', '-', 'bar'));

        self::assertSame(
            'foo-bar',
            $this->executeDoctrineQuery($select)->fetchOne(),
        );
    }

    public function testLpad(): void
    {
        $select = new Select();
        $select->columnRaw(new Lpad('foo', 7, 'ab'));

        self::assertSame(
            'ababfoo',
            $this->executeDoctrineQuery($select)->fetchOne(),
        );
    }
}
