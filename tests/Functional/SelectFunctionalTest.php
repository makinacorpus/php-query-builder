<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\FunctionCall;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;

class SelectFunctionalTest extends DoctrineTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE foo
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE bar
                SQL
            );
        } catch (\Throwable) {}

        $this->getBridge()->executeStatement(
            <<<SQL
            CREATE TABLE foo (
                id int NOT NULL,
                name text DEFAULT NULL
            )
            SQL
        );

        $this->getBridge()->executeStatement(
            <<<SQL
            CREATE TABLE bar (
                foo_id int NOT NULL,
                data json DEFAULT NULL
            )
            SQL
        );
    }

    public function testSelectInt(): void
    {
        $select = new Select();
        $select->columnRaw('1');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectBool(): void
    {
        $select = new Select();
        $select->column(new Value(true, 'bool'));

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectFunction(): void
    {
        $select = new Select();
        $select->column(new FunctionCall('now'));

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectCount(): void
    {
        self::markTestSkipped("Implement me.");
    }

    public function testFrom(): void
    {
        $select = new Select('foo');
        $select->join('bar', 'other.foo_id = foo.id', 'other');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testFromSelect(): void
    {
        $nested = new Select('foo');

        $select = new Select($nested);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testFromSelectWhere(): void
    {
        $nested = new Select('bar');

        $select = new Select('foo');
        $select->from($nested, 'other');
        $select->whereRaw('other.foo_id = foo.id');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectFromConstantTable(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MARIADB, '10.3', 'MariaDB supports VALUES statement since version 10.3.');
        $this->skipIfDatabase(AbstractBridge::SERVER_MARIADB, 'MariaDB does not support VALUES column aliasing.');

        $table = new ConstantTable();
        $table->columns(['a', 'b' , 'c']);
        $table->row(['foo', 1, new \DateTimeImmutable()]);
        $table->row(['bar', 2, new \DateTimeImmutable('now +7 day')]);

        $select = new Select($table);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testJoin(): void
    {
        $select = new Select('foo');
        $select->join('bar', 'other.foo_id = foo.id', 'other');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testJoinSelect(): void
    {
        $nested = new Select('bar');

        $select = new Select('foo');
        $select->join($nested, 'other.foo_id = foo.id', 'other');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testJoinSelectOn(): void
    {
        $nested = new Select('bar');

        $select = new Select('foo');
        $select->join($nested, 'other.foo_id = foo.id', 'other');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testJoinConstantTable(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MARIADB, '10.3', 'MariaDB supports VALUES statement since version 10.3.');
        $this->skipIfDatabase(AbstractBridge::SERVER_MARIADB, 'MariaDB does not support VALUES column aliasing.');

        $table = new ConstantTable();
        $table->columns(['a', 'b' , 'c']);
        $table->row(['foo', 1, new \DateTimeImmutable()]);
        $table->row(['bar', 1, new \DateTimeImmutable('now +7 day')]);

        $select = new Select('foo');
        // We need to CAST, otherwise PostgreSQL will accept it as a "text".
        $select->join($table, new Raw('? = foo.id', [new Cast(new ColumnName('other.a'), 'int')]), 'other');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testWithJoin(): void
    {
        $nested = new Select('bar');

        $select = new Select('foo');
        $select->with('other', $nested);
        $select->join('other', 'other.foo_id = foo.id');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testWithConstantTableJoin(): void
    {
        $table = new ConstantTable();
        $table->columns(['a', 'b' , 'c']);
        $table->row(['foo', 1, new \DateTimeImmutable()]);
        $table->row(['bar', 1, new \DateTimeImmutable('now +7 day')]);

        $select = new Select('foo');
        $select->with('other', $table);
        // We need to CAST, otherwise PostgreSQL will accept it as a "text".
        $select->join('other', new Raw('? = foo.id', [new Cast(new ColumnName('other.a'), 'int')]));

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testWhere(): void
    {
        self::markTestSkipped("Implement me.");
    }

    public function testHaving(): void
    {
        self::markTestSkipped("Implement me.");
    }
}
