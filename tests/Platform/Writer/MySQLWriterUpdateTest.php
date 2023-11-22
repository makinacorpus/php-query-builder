<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Tests\Query\UpdateTest;

class MySQLWriterUpdateTest extends UpdateTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQLWriter(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }

    public function testJoinNested(): void
    {
        $query = new Update('bar');
        $query->set('foo', 1);
        $query->join(new Select('foo'), 'a = b', 'foo_2');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            update "bar"
            inner join (
                select * from "foo"
            ) as "foo_2" on (
                a = b
            )
            inner join "fizz"
                on (buzz)
            set
                "bar"."foo" = #1
            SQL,
            $query
        );
    }

    public function testMultipleJoin(): void
    {
        $query = new Update('bar');
        $query->set('foo', 1);
        $query->join("foo", 'a = b');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            update "bar"
            inner join "foo"
                on (a = b)
            inner join "fizz"
                on (buzz)
            set
                "bar"."foo" = #1
            SQL,
            $query
        );
    }

    public function testReturning(): void
    {
        // MySQL does not support RETURNING.
        self::expectNotToPerformAssertions();
    }
}
