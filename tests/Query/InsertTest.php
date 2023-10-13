<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class InsertTest extends UnitTestCase
{
    public function testClone()
    {
        $query = new Insert('d');
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
        $query = new Insert('a');

        self::assertFalse($query->returns());

        $query->returning();

        self::assertTrue($query->returns());
    }

    private function createSelectQuery(): Query
    {
        return (new Select('other_table'))
            ->columns(['foo', 'bar'])
            ->where('baz', true)
        ;
    }

    public function testRaiseErrorIfValuesIsCalledAfterQuery(): void
    {
        $insert = (new Insert('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createSelectQuery()
            )
        ;

        self::expectExceptionMessageMatches('/mutually exclusive/');
        $insert->values(['foo', 'bar']);
    }

    public function testInsertDefaultValues(): void
    {
        $insert = new Insert('some_table');
        $insert->values([]);

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
            default values
            SQL,
            $insert
        );
    }

    public function testRaiseErrorIfQueryIsCalledAfterValues(): void
    {
        $insert = (new Insert('some_table'))
            ->columns(['pif', 'paf'])
            ->values(['foo', 'bar'])
        ;

        self::expectExceptionMessageMatches('/was already set/');
        $insert->query(
            $this->createSelectQuery()
        );
    }

    public function testQueryInsertBasics(): void
    {
        $insert = (new Insert('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createSelectQuery()
            )
        ;

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
                ("pif", "paf")
            select
                "foo",
                "bar"
            from "other_table"
            where
                "baz" = #1
            SQL,
            $insert
        );
    }

    public function testInsertWithoutQueryFails(): void
    {
        $insert = new Insert('some_table');
        $insert->columns(['pif', 'paf']);

        self::expectExceptionMessageMatches('/was not set/');
        self::createTestWriter()->prepare($insert);
    }

    public function testInsertValuesUsesColumnsFromFirst(): void
    {
        $insert = new Insert('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
                ("foo", "int")
            values
                (#1, #2)
            SQL,
            $insert
        );
    }

    public function testInsertValuesIngoreKeysFromNext(): void
    {
        $insert = new Insert('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pif' => 'pouf',
            'paf' => 3,
        ]);

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
                ("foo", "int")
            values
                (#1, #2),
                (#3, #4)
            SQL,
            $insert
        );
    }

    public function testInsertValuesWithColumnCall(): void
    {
        $insert = new Insert('some_table');
        $insert->columns(['a', 'b']);
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pouf',
            3,
        ]);

        self::assertSameSql(
            <<<SQL
            insert into "some_table"
                ("a", "b")
            values
                (#1, #2),
                (#3, #4)
            SQL,
            $insert
        );
    }
}
