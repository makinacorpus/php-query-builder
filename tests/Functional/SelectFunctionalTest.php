<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;

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
                name varchar DEFAULT NULL,
                date timestamp
            )
            SQL
        );

        $this->getBridge()->executeStatement(
            <<<SQL
            CREATE TABLE bar (
                foo_id int NOT NULL,
                data varchar DEFAULT NULL
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
        $select->column(new CurrentTimestamp());

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectDistinct(): void
    {
        $select = new Select('foo');
        $select->distinct();

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectCountStart(): void
    {
        $select = new Select('foo');
        $select->count();

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectCountColumn(): void
    {
        $select = new Select('foo');
        $select->count('name');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectCountDistinct(): void
    {
        $select = new Select('foo');
        $select->count('name', null, true);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
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
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');
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
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');
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
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $nested = new Select('bar');

        $select = new Select('foo');
        $select->with('other', $nested);
        $select->join('other', 'other.foo_id = foo.id');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testWithConstantTableJoin(): void
    {
        // https://learn.microsoft.com/en-us/sql/t-sql/queries/table-value-constructor-transact-sql?view=sql-server-ver16
        $this->skipIfDatabase(AbstractBridge::SERVER_SQLSERVER, 'SQL Server only accepts Table Value Constructor in SELECT, FROM and INSERT.');
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

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

    public function testAggregateFilter(): void
    {
        $select = new Select('foo');

        $select
            ->createColumnAgg('max', 'date', 'max_date')
            ->filterRaw('id < 10')
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testAggregateOverPartitionBy(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->createColumnAgg('max', 'date', 'max_date')
            ->over('name')
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testAggregateOverOrderBy(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->createColumnAgg('row_number', null, 'rownum')
            ->createOver()
            ->orderBy('id', Query::ORDER_DESC)
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testAggregateOverPartitionByOrderBy(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->createColumnAgg('row_number', null, 'rownum')
            ->createOver()
            ->partitionBy('name')
            ->orderBy('id', Query::ORDER_DESC)
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testAggregateOverEmpty(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->createColumnAgg('max', 'date', 'max_date')
            ->over()
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testAggregateFilterOver(): void
    {
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->createColumnAgg('max', 'date', 'max_date')
            ->filterRaw('id < 10')
            ->over('name', 'id')
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testWindowAfterFrom(): void
    {
        $this->skipIfDatabase(AbstractBridge::SERVER_SQLSERVER);
        $this->skipIfDatabaseLessThan(AbstractBridge::SERVER_MYSQL, '8.0');

        $select = new Select('foo');

        $select
            ->window('some_window', 'name', 'id')
            ->createColumnAgg('max', 'date', 'max_date')
            ->filterRaw('id < 10')
            ->overWindow('some_window')
        ;

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testLimit(): void
    {
        $select = new Select('foo');
        $select->orderBy('id');
        $select->limit(2);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testOffset(): void
    {
        $select = new Select('foo');
        $select->orderBy('id');
        $select->offset(2);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testLimitOffset(): void
    {
        $select = new Select('foo');
        $select->orderBy('id');
        $select->range(2, 2);

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testGroupBy(): void
    {
        self::markTestSkipped("Implement me.");
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
