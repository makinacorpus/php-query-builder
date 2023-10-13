<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class MergeTest extends UnitTestCase
{
    public function testClone()
    {
        $query = new Merge('d');
        $query
            ->with('sdf', new ConstantTable([[1, 2]]))
            ->columns(['bar'])
            ->values(['foo'])
            ->returning('*')
        ;

        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testReturns(): void
    {
        $query = new Merge('a');

        self::assertFalse($query->returns());

        $query->returning();

        self::assertTrue($query->returns());
    }

    private function createUsingQuery(): Query
    {
        return (new Select('table2'))
            ->column('a')
            ->column('b')
            ->column('c')
            ->column('d')
        ;
    }

    public function testRaiseErrorIfValuesIsCalledAfterQuery(): void
    {
        $insert = (new Merge('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::expectExceptionMessageMatches('/mutually exclusive/');
        $insert->values(['foo', 'bar']);
    }

    public function testRaiseErrorIfQueryIsCalledAfterValues(): void
    {
        $insert = (new Merge('some_table'))
            ->columns(['pif', 'paf'])
            ->values(['foo', 'bar'])
        ;

        self::expectExceptionMessageMatches('/was already set/');
        $insert->query(
            $this->createUsingQuery()
        );
    }

    public function testStringWithDotKeyRaiseError(): void
    {
        $query = (new Merge('table1'))
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::expectExceptionMessageMatches('/column names in the primary/');
        $query->setKey(['foo.bar']);
    }

    public function testNonStringKeyRaiseError(): void
    {
        $query = new Merge('table1');

        self::expectExceptionMessageMatches('/column names in the primary/');
        $query->setKey([new \DateTimeImmutable()]);
    }

    public function testInvalidConflictBehaviourRaiseError(): void
    {
        $query = new Merge('table1');

        self::expectExceptionMessageMatches('/behaviours must be one/');
        $query->onConflict(7);
    }

    public function testValueOnConflictIgnore(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using
                values (
                    #1, #2, #3, #4
                ), (
                    #5, #6, #7, #8
                ) as "upsert"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testValueOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->setKey(['foo', 'bar'])
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using
                values (
                    #1, #2, #3, #4
                ), (
                    #5, #6, #7, #8
                ) as "upsert"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testValueOnConflictUpdate(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->setKey(['foo', 'bar'])
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using
                values (
                    #1, #2, #3, #4
                ), (
                    #5, #6, #7, #8
                ) as "upsert"
            when matched then
                update set
                    "fizz" = "upsert"."fizz",
                    "buzz" = "upsert"."buzz"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testValueOnConflictUpdateWithoutKey(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using
                values (
                    #1, #2, #3, #4
                ), (
                    #5, #6, #7, #8
                ) as "upsert"
            when matched then
                update set
                    "foo" = "upsert"."foo",
                    "bar" = "upsert"."bar",
                    "fizz" = "upsert"."fizz",
                    "buzz" = "upsert"."buzz"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testQueryOnConflictIgnore(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using (
                select "a", "b", "c", "d" from "table2"
            ) as "upsert"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testQueryOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->setKey(['foo', 'bar'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using (
                select "a", "b", "c", "d" from "table2"
            ) as "upsert"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testQueryOnConflictUpdate(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->setKey(['foo', 'bar'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using (
                select "a", "b", "c", "d" from "table2"
            ) as "upsert"
            when matched then
                update set
                    "fizz" = "upsert"."fizz",
                    "buzz" = "upsert"."buzz"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }

    public function testQueryOnConflictUpdateWithoutKey(): void
    {
        $query = (new Merge('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(
            <<<SQL
            merge into "table1"
            using (
                select "a", "b", "c", "d" from "table2"
            ) as "upsert"
            when matched then
                update set
                    "foo" = "upsert"."foo",
                    "bar" = "upsert"."bar",
                    "fizz" = "upsert"."fizz",
                    "buzz" = "upsert"."buzz"
            when not matched then
                insert into "table1" (
                    "foo", "bar", "fizz", "buzz"
                ) values (
                    "upsert"."foo",
                    "upsert"."bar",
                    "upsert"."fizz",
                    "upsert"."buzz"
                )
            SQL,
            $query
        );
    }
}
