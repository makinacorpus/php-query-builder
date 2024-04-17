<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\Lpad;
use MakinaCorpus\QueryBuilder\Expression\Rpad;
use MakinaCorpus\QueryBuilder\Expression\StringHash;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Vendor;

class TextFunctionalTest extends DoctrineTestCase
{
    public function testConcat(): void
    {
        $select = new Select();
        $select->columnRaw(new Concat('foo', '-', 'bar'));

        self::assertSame(
            'foo-bar',
            $this->executeQuery($select)->fetchOne(),
        );
    }

    public function testMd5(): void
    {
        $this->skipIfDatabase(Vendor::SQLITE, 'SQLite does not have any hash function.');
        $this->skipIfDatabase(Vendor::SQLSERVER, 'SQL Server actually returns a hash, but not the right one ?!');

        $select = new Select();
        $select->columnRaw(new StringHash('foo', 'md5'));

        self::assertSame(
            'acbd18db4cc2f85cedef654fccc4a4d8',
            $this->executeQuery($select)->fetchOne(),
        );
    }

    public function testSha1(): void
    {
        $this->skipIfDatabase(Vendor::POSTGRESQL, 'pgcrypto extension must be enabled.');
        $this->skipIfDatabase(Vendor::SQLITE, 'SQLite does not have any hash function.');
        $this->skipIfDatabase(Vendor::SQLSERVER, 'SQL Server actually returns a hash, but not the right one ?!');

        $select = new Select();
        $select->columnRaw(new StringHash('foo', 'sha1'));

        self::assertSame(
            '0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33',
            $this->executeQuery($select)->fetchOne(),
        );
    }

    public function testLpad(): void
    {
        $select = new Select();
        $select->columnRaw(new Lpad('foo', 7, 'ab'));

        self::assertSame(
            'ababfoo',
            $this->executeQuery($select)->fetchOne(),
        );
    }

    public function testLpadWithFloatInput(): void
    {
        $select = new Select();
        $select->columnRaw(new Lpad(new Value(10, 'int'), 5, 'a'));

        self::assertSame(
            'aaa10',
            $this->executeQuery($select)->fetchOne(),
        );
    }

    public function testRpad(): void
    {
        $select = new Select();
        $select->columnRaw(new Rpad('foo', 7, 'ab'));

        self::assertSame(
            'fooabab',
            $this->executeQuery($select)->fetchOne(),
        );
    }
}
