<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Tests\Query\DeleteTest;

class MySQLWriterDeleteTest extends DeleteTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQLWriter(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testMultipleJoin(): void
    {
        $query = new Delete('bar');
        $query->join("foo", 'a = b');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            delete "bar".*
            from "bar"
            inner join "foo"
                on (a = b)
            inner join "fizz"
                on (buzz)
            SQL,
            $query
        );
    }

    public function testEmptyDelete()
    {
        $query = new Delete('some_table');

        self::assertSameSql(
            <<<SQL
            delete "some_table".*
            from "some_table"
            SQL,
            $query
        );
    }

    public function testWhere()
    {
        $query = new Delete('some_table');

        $query->where('a', 12);

        self::assertSameSql(
            <<<SQL
            delete "some_table".*
            from "some_table"
            where
                ("a" = #1)
            SQL,
            $query
        );
    }

    public function testExpression()
    {
        $query = new Delete('some_table');

        $query->whereRaw('true');

        self::assertSameSql(
            <<<SQL
            delete "some_table".*
            from "some_table"
            where
                (true)
            SQL,
            $query
        );
    }

    public function testReturningStar()
    {
        // MySQL does not support RETURNING.
        self::expectNotToPerformAssertions();
    }

    public function testReturningColumn()
    {
        // MySQL does not support RETURNING.
        self::expectNotToPerformAssertions();
    }

    public function testRetuningColumnWithAlias()
    {
        // MySQL does not support RETURNING.
        self::expectNotToPerformAssertions();
    }

    public function testReturningRaw()
    {
        // MySQL does not support RETURNING.
        self::expectNotToPerformAssertions();
    }
}
