<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;

class DateFunctionalTest extends DoctrineTestCase
{
    public function testDateAdd(): void
    {
        $select = new Select();

        $expr = $select->expression();

        $select->column(
            $expr->dateAdd(
                '2022-03-13 11:00:00',
                [
                    'hour' => 7,
                    'minute' => 12,
                ],
            )
        );

        $value = $this->executeQuery($select)->fetchRow()->get(0, \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeInterface::class, $value);
        self::assertSame('2022-03-13 18:12:00', $value->format('Y-m-d H:i:s'));
    }

    public function testDateSub(): void
    {
        $select = new Select();

        $expr = $select->expression();

        $select->column(
            $expr->dateSub(
                '2022-03-13 11:00:00',
                [
                    'hour' => 7,
                    'minute' => 12,
                ],
            )
        );

        $value = $this->executeQuery($select)->fetchRow()->get(0, \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeInterface::class, $value);
        self::assertSame('2022-03-13 03:48:00', $value->format('Y-m-d H:i:s'));
    }
}