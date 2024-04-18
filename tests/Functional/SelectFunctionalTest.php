<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Vendor;

class SelectFunctionalTest extends DoctrineTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        try {
            $this->getDatabaseSession()->executeStatement(
                <<<SQL
                DROP TABLE foo
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getDatabaseSession()->executeStatement(
                <<<SQL
                DROP TABLE bar
                SQL
            );
        } catch (\Throwable) {}

        switch ($this->getDatabaseSession()->getVendorName()) {

            case Vendor::MARIADB:
            case Vendor::MYSQL:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name text DEFAULT NULL,
                        date datetime DEFAULT now()
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data text DEFAULT NULL
                    )
                    SQL
                );
                break;

            case Vendor::SQLSERVER:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name nvarchar(500) DEFAULT NULL,
                        date datetime2(6) DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data nvarchar(500) DEFAULT NULL
                    )
                    SQL
                );
                break;

            default:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name text DEFAULT NULL,
                        date timestamp DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data text DEFAULT NULL
                    )
                    SQL
                );
                break;
        }
    }

    public function testCurrentDatabase(): void
    {
        $select = new Select();
        $expr = $select->expression();

        $select->column($expr->currentDatabase());

        $value = $this->executeQuery($select)->fetchRow()->get(0, 'string');

        if ($this->ifDatabase(Vendor::SQLITE)) {
            self::assertSame('main', $value);
        } else {
            self::assertSame('test_db', $value);
        }
    }

    public function testCurrentSchema(): void
    {
        $select = new Select();
        $expr = $select->expression();

        $select->column($expr->currentSchema());

        $value = $this->executeQuery($select)->fetchRow()->get(0, 'string');

        if (!$this->ifDatabase(Vendor::SQLSERVER)) {
            self::assertSame('public', $value);
        }
    }

    public function testSelectInt(): void
    {
        $select = new Select();
        $select->columnRaw('1');

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectRandom(): void
    {
        $select = new Select();
        $select->column(new Random());

        $this->executeStatement($select);

        self::expectNotToPerformAssertions();
    }

    public function testSelectRandomInt(): void
    {
        $select = new Select();
        $select->column(new RandomInt(3, 10));

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

    public function testFromFromSelect(): void
    {
        $select = new Select('foo');
        $select->from(
            new Select('bar'),
            'bar',
        );
        $select->whereRaw('bar.foo_id = foo.id');

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
        $this->skipIfDatabase(Vendor::MARIADB, 'MariaDB does not support VALUES column aliasing.');
        $this->skipIfDatabase(Vendor::SQLITE, 'SQLite requires you to convert VALUES in SELECT to VALUES in an aliased CTE');
        $this->skipIfDatabaseLessThan(Vendor::MARIADB, '10.3', 'MariaDB supports VALUES statement since version 10.3.');
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabase(Vendor::MARIADB, 'MariaDB does not support VALUES column aliasing.');
        $this->skipIfDatabase(Vendor::SQLITE, 'SQLite requires you to convert VALUES in SELECT to VALUES in an aliased CTE');
        $this->skipIfDatabaseLessThan(Vendor::MARIADB, '10.3', 'MariaDB supports VALUES statement since version 10.3.');
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabase(Vendor::SQLSERVER, 'SQL Server only accepts Table Value Constructor in SELECT, FROM and INSERT.');
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
        $this->skipIfDatabase(Vendor::SQLSERVER);
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

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
