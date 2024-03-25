<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Writer\Writer;

class SelectTest extends UnitTestCase
{
    protected function setUp(): void
    {
        // Test are legacy one copied from makinacorpus/goat-query,
        // formatting was not exactly the same (default placeholder
        // configuration is different). Restore it.
        self::setTestWriter(new Writer(new StandardEscaper('?', null)));
    }

    protected function tearDown(): void
    {
        // Restore default writer.
        self::setTestWriter(null);
    }

    public function testClone(): void
    {
        $query = new Select();
        $query
            ->with('sdf', new ConstantTable([[1, 2]]))
            ->from('a')
            ->from('b')
            ->join('c')
            ->column('bar')
            ->orderBy('baz')
            ->having('fizz', 'buzz')
            ->where('foo', 42)
        ;

        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testReturns(): void
    {
        $query = new Select();

        self::assertTrue($query->returns());
    }

    public function testHasColumn(): void
    {
        $query = new Select();

        self::assertFalse($query->hasColumn('foo'));

        $query->column('foo');

        self::assertTrue($query->hasColumn('foo'));
    }

    public function testSelectColumnAsRow(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testSelectColumnAsConstantTable(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testEmptySelect(): void
    {
        $query = new Select('some_table');

        self::assertSameSql(
            'select * from "some_table"',
            $query
        );
    }

    public function testSelectDistinct(): void
    {
        $query = new Select('some_table');
        $query->distinct();

        self::assertSameSql(
            'select distinct * from "some_table"',
            $query
        );
    }

    public function testSelectCountStart(): void
    {
        $query = new Select('some_table');
        $query->count();

        self::assertSameSql(
            'select "count"(*) from "some_table"',
            $query
        );
    }

    public function testSelectCountColumn(): void
    {
        $query = new Select('some_table');
        $query->count('name');

        self::assertSameSql(
            'select "count"("name") from "some_table"',
            $query
        );
    }

    public function testSelectCountDistinct(): void
    {
        $query = new Select('some_table');
        $query->count('name', null, true);

        self::assertSameSql(
            'select "count"(distinct "name") from "some_table"',
            $query
        );
    }

    public function testHavingWithOrderAndLimit(): void
    {
        $query = (new Select('a'))
            ->having('b', 12)
            ->orderBy('foo')
            ->groupBy('bar')
            ->range(12, 7)
        ;

        self::assertSameSql(
            'select * from "a" group by "bar" having "b" = ? order by "foo" asc limit 12 offset 7',
            $query
        );
    }

    public function testUnion(): void
    {
        $query = new Select('some_table');

        $query->createUnion('other_table');
        $query->createUnion('again_and_again');

        self::assertSameSql(
            <<<SQL
            select * from "some_table"
            union
            select * from "other_table"
            union
            select * from "again_and_again"
            SQL,
            $query
        );
    }

    public function testUnionRecursive(): void
    {
        $query = new Select('some_table');

        $other = $query->createUnion('other_table');

        $other->createUnion('another_one');

        self::assertSameSql(
            <<<SQL
            select * from "some_table"
            union
            select * from "other_table"
            union
            select * from "another_one"
            SQL,
            $query
        );
    }

    public function testForUpdate(): void
    {
        $query = new Select('some_table');
        self::assertFalse($query->isForUpdate());

        $query->forUpdate();
        self::assertTrue($query->isForUpdate());

        self::assertSameSql(
            'select * from "some_table" for update',
            $query
        );
    }

    public function testPerformOnly(): void
    {
        $query = new Select('some_table');
        self::assertTrue($query->willReturnRows());

        $query->performOnly();
        self::assertFalse($query->willReturnRows());

        self::assertSameSql(
            'select * from "some_table"',
            $query,
            "Will return row does not change formatting"
        );
    }

    public function testColumnWithName(): void
    {
        $query = new Select('some_table');

        $query->column('a');

        self::assertSameSql(
            'select "a" from "some_table"',
            $query
        );
    }

    public function testColumnWithNameAndAlias(): void
    {
        $query = new Select('some_table');

        $query->column('a', 'my_alias');

        self::assertSameSql(
            'select "a" as "my_alias" from "some_table"',
            $query
        );
    }

    public function testColumnWithTableAndName(): void
    {
        $query = new Select('some_table');

        $query->column('foo.a');

        self::assertSameSql(
            'select "foo"."a" from "some_table"',
            $query
        );
    }

    public function testColumnWithExpression(): void
    {
        $query = new Select('some_table');

        $query->column(new Raw('count(distinct foo)'), 'bar');

        self::assertSameSql(
            'select count(distinct foo) as "bar" from "some_table"',
            $query
        );
    }

    public function testColumnRawWithColumnNameDoesNotEscape(): void
    {
        $query = new Select('some_table');
        $query->columnRaw('foo.a', 'my_alias');

        self::assertSameSql(
            'select foo.a as "my_alias" from "some_table"',
            $query
        );
    }

    public function testColumnRawWithExpressionInstanceAndArgumentsRaiseException(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/arguments/');

        $query->columnRaw(new Raw('count(*)'), null, 12);
    }

    public function testColumnRawWithEmptyStringRaiseError(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $query->columnRaw('');
    }

    public function testColumnRawWithNonArrayArguments(): void
    {
        $query = new Select('some_table');

        $query->columnRaw('sum(?)', null, 12);

        self::assertSameSql(
            'select sum(?) from "some_table"',
            $query
        );
    }

    public function testColumnWithCallbackWhichReturnsStringGetsEscaped(): void
    {
        $query = new Select('some_table');

        $query->column(
            function () {
                return "foo.bar";
            },
            'result'
        );

        self::assertSameSql(
            'select "foo"."bar" as "result" from "some_table"',
            $query
        );
    }

    public function testColumnWithCallbackWhichReturnsExpression(): void
    {
        $query = new Select('some_table');

        $query->column(
            function () {
                return new Raw("foo.bar");
            },
            'result'
        );

        self::assertSameSql(
            'select foo.bar as "result" from "some_table"',
            $query
        );
    }

    public function testColumnWithCallbackWhichReturnsStringIsNotEscaped(): void
    {
        $query = new Select('some_table');

        $query->columnRaw(
            function () {
                return "foo.bar";
            },
            'result'
        );

        self::assertSameSql(
            'select foo.bar as "result" from "some_table"',
            $query
        );
    }

    public function testColumnRawWithCallback(): void
    {
        $query = new Select('some_table');

        $query->column(
            function () {
                return new Raw("count(*)");
            },
            'result'
        );

        self::assertSameSql(
            'select count(*) as "result" from "some_table"',
            $query
        );
    }

    public function testColumns(): void
    {
        $query = new Select('some_table');

        $query->columns([
            // Just a column, no alias
            'foo.bar',

            // A colum, with an alias
            'id' => 'a.b',

            // An expresssion, no alias
            new Raw('count(a) as a'),

            // An expression, with alias
            'b' => new Raw('count(b)'),

            // A callback, no alias
            function () {
                return new Raw('count(c) as c');
            },

            // A callback, with alias
            'd' => function () {
                return new Raw('count(d)');
            },

            // A short arrow function, no alias
            fn () => new Raw('count(e) as e'),

            // A short arrow function, with alias
            'f' => fn () => new Raw('count(f)'),
        ]);

        self::assertSameSql('
            select
                "foo"."bar",
                "a"."b" as "id",
                count(a) as a,
                count(b) as "b",
                count(c) as c,
                count(d) as "d",
                count(e) as e,
                count(f) as "f"
            from "some_table"',
            $query
        );
    }

    public function testCondition(): void
    {
        $query = new Select('some_table');

        $query->where('foo', 12);

        self::assertSameSql(
            'select * from "some_table" where "foo" = ?',
            $query
        );
    }

    public function testConditionWithExpression(): void
    {
        $query = new Select('some_table');

        $query->where('bar', new Raw('12'));

        self::assertSameSql(
            'select * from "some_table" where "bar" = 12',
            $query
        );
    }

    public function testConditionWithValueExpressionWillCast(): void
    {
        $query = new Select('some_table');

        $query->where('baz', new Value(12, 'json'));

        $formatted = self::createTestWriter()->prepare($query);

        self::assertSameSql(
            'select * from "some_table" where "baz" = ?',
            $query
        );

        self::assertSameType(Type::json(), $formatted->getArguments()->getTypeAt(0));
    }

    public function testConditionWithCallbackCastAsArgument(): void
    {
        $query = new Select('some_table');

        $query->where('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" where "boo" = ?',
            $query
        );
    }

    public function testConditionWithWhereInstance(): void
    {
        $query = new Select('some_table');

        $query->where((new Where(Where::OR))
            ->isNull('foo')
            ->isNull('bar')
        );

        self::assertSameSql(
            'select * from "some_table" where ("foo" is null or "bar" is null)',
            $query
        );
    }

    public function testExpressionWithWhereInstance()
    {
        $query = new Select('some_table');

        $query->whereRaw((new Where(Where::OR))
            ->isNull('foo')
            ->isNull('bar')
        );

        self::assertSameSql(
            'select * from "some_table" where ("foo" is null or "bar" is null)',
            $query
        );
    }

    public function testConditionWithCallbackReturningRaw(): void
    {
        $query = new Select('some_table');

        $query->where('baa', fn () => new Raw('now()'), '<');

        self::assertSameSql(
            'select * from "some_table" where "baa" < now()',
            $query
        );
    }

    public function testConditionWithCallbackReturningNothing(): void
    {
        $query = new Select('some_table');

        $query->where(function (Where $where) {
            $where
                ->isEqual('id', 12)
                ->isGreaterOrEqual('birthdate', new \DateTimeImmutable('2019-09-24'))
            ;
        });

        self::assertSameSql(
            'select * from "some_table" where "id" = ? and "birthdate" >= ?',
            $query
        );
    }

    public function testConditionWithShortArrowFunction(): void
    {
        $query = new Select('some_table');

        $query->where(fn (Where $where) => $where->isLess('foo', 'bar'));

        self::assertSameSql(
            'select * from "some_table" where "foo" < ?',
            $query
        );
    }

    public function testWhereNesting(): void
    {
        $query = new Select('some_table');

        $query
            ->getWhere()
                ->isEqual('foo', 'bar')
                ->isBetween('bla', 1, 2)
            ->end()
            ->range(1)
        ;

        self::assertSameSql(
            <<<SQL
            select *
            from "some_table"
            where
                "foo" = ?
                and "bla" between ? and ?
            limit 1
            SQL,
            $query
        );
    }

    public function testWhereNestingNesting(): void
    {
        $query = new Select('some_table');

        $query
            ->getWhere()
                ->or()
                    ->isEqual('foo', 'bar')
                    ->isBetween('bla', 1, 2)
                ->end()
            ->end()
            ->range(1)
        ;

        self::assertSameSql(
            <<<SQL
            select *
            from "some_table"
            where
                (
                    "foo" = ?
                    or "bla" between ? and ?
                )
            limit 1
            SQL,
            $query
        );
    }

    public function testExpression(): void
    {
        $query = new Select('some_table');

        $query->whereRaw('a < b');

        self::assertSameSql(
            'select * from "some_table" where a < b',
            $query
        );
    }

    public function testExpressionWithCallback(): void
    {
        $query = new Select('some_table');

        $query->whereRaw(function (Where $where) {
            $where->isEqual('a', 56);
        });

        self::assertSameSql(
            'select * from "some_table" where "a" = ?',
            $query
        );
    }

    public function testExpressionWithShortArrowFunction(): void
    {
        $query = new Select('some_table');

        $query->whereRaw(fn (Where $where) => $where->isLess('foo', 'bar'));

        self::assertSameSql(
            'select * from "some_table" where "foo" < ?',
            $query
        );
    }

    public function testExpressionWithEmptyStringRaiseError(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $query->whereRaw('');
    }

    public function testHaving(): void
    {
        $query = new Select('some_table');

        $query->having('foo', 12);

        self::assertSameSql(
            'select * from "some_table" having "foo" = ?',
            $query
        );
    }

    public function testHavingWithExpression(): void
    {
        $query = new Select('some_table');

        $query->having('bar', new Raw('12'));

        self::assertSameSql(
            'select * from "some_table" having "bar" = 12',
            $query
        );
    }

    public function testHavingWithValueExpressionWillCast(): void
    {
        $query = new Select('some_table');

        $query->having('baz', new Value(12, 'json'));

        $formatted = self::createTestWriter()->prepare($query);

        self::assertSameSql(
            'select * from "some_table" having "baz" = ?',
            $formatted
        );

        self::assertSameType(Type::json(), $formatted->getArguments()->getTypeAt(0));
    }

    public function testHavingWithCallbackCastAsArgument(): void
    {
        $query = new Select('some_table');

        $query->having('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" having "boo" = ?',
            $query
        );
    }

    public function testHavingWithCallbackReturningRaw(): void
    {
        $query = new Select('some_table');

        $query->having('baa', fn () => new Raw('now()'), '<');

        self::assertSameSql(
            'select * from "some_table" having "baa" < now()',
            $query
        );
    }

    public function testHavingRaw(): void
    {
        $query = new Select('some_table');

        $query->havingRaw('a < b');

        self::assertSameSql(
            'select * from "some_table" having a < b',
            $query
        );
    }

    public function testHavingRawWithCallback(): void
    {
        $query = new Select('some_table');

        $query->havingRaw(fn (Where $where) => $where->isEqual('a', 56));
        $query->havingRaw(function (Where $where) { $where->isEqual('b', 78); });

        self::assertSameSql(
            'select * from "some_table" having "a" = ? and "b" = ?',
            $query
        );
    }

    public function testHavingNesting(): void
    {
        $query = new Select('some_table');

        $query
            ->getHaving()
                ->isEqual('foo', 'bar')
                ->isBetween('bla', 1, 2)
            ->end()
            ->range(1)
        ;

        self::assertSameSql(
            <<<SQL
            select *
            from "some_table"
            having
                "foo" = ?
                and "bla" between ? and ?
            limit 1
            SQL,
            $query
        );
    }

    public function testFromSelect(): void
    {
        $query = new Select('foo');
        $query->from(
            new Select('bar'),
            'bar',
        );
        $query->whereRaw('bar.id = foo.id');

        self::assertSameSql(
            <<<SQL
            select *
            from "foo", (
                select *
                from "bar"
            ) as "bar"
            where
                bar.id = foo.id
            SQL,
            $query
        );
    }

    public function testJoinSelect(): void
    {
        $query = new Select('foo');
        $query->join(
            new Select('bar'),
            'bar.id = foo.id',
            'bar',
        );

        self::assertSameSql(
            <<<SQL
            select *
            from "foo"
            inner join (
                select *
                from "bar"
            ) as "bar" on (
                bar.id = foo.id
            )
            SQL,
            $query
        );
    }

    public function testExpressionAsJoin(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select('foo');
        $query->join($expression, 'true');

        self::assertSameSql(
            <<<SQL
            select * from "foo"
            inner join (
                values (?, ?, ?)
            ) as "mcqb_1" on (true)
            SQL,
            $query
        );
    }

    public function testExpressionAsJoinWithAlias(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select('foo');
        $query->join($expression, 'true', 'mooh');

        self::assertSameSql(
            <<<SQL
            select * from "foo"
            inner join (
                values (?, ?, ?)
            ) as "mooh" on (true)
            SQL,
            $query
        );
    }

    public function testJoinWithoutConditionIsCrossJoin(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select('foo');
        $query->join($expression, null, 'mooh');

        self::assertSameSql(
            <<<SQL
            select * from "foo"
            cross join (
                values (?, ?, ?)
            ) as "mooh"
            SQL,
            $query
        );
    }

    public function testExpressionAsJoinWithAliasInExpression()
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select(new Aliased($expression, 'f.u.b.a.r.'));

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "f.u.b.a.r."
            SQL,
            $query
        );
    }

    public function testExpressionAsTable(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);
        $expression->row(["a", "b", "c"]);

        $query = new Select($expression);

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?),
                (?, ?, ?)
            ) as "mcqb_1"
            SQL,
            $query
        );
    }

    public function testExpressionAsTableWithAlias(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select($expression, 'foobar');

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "foobar"
            SQL,
            $query
        );
    }

    public function testExpressionAsTableWithAliasAndColumnAliases(): void
    {
        $expression = new ConstantTable();
        $expression->columns(['foo', 'bar', 'baz']);
        $expression->row([1, 2, 3]);

        $query = new Select($expression, 'foobar');

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "foobar" ("foo", "bar", "baz")
            SQL,
            $query
        );
    }

    public function testExpressionInCteWithAlias(): void
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select('temp_table');
        $query->with('temp_table', $expression);

        self::assertSameSql(
            <<<SQL
            with "temp_table" as (values (?, ?, ?))
            select *
            from "temp_table"
            SQL,
            $query
        );
    }

    public function testExpressionInCteWithAliasAndColumnAliases(): void
    {
        $expression = new ConstantTable();
        $expression->columns(['foo', 'bar', 'baz']);
        $expression->row([1, 2, 3]);

        $query = new Select('temp_table');
        $query->with('temp_table', $expression);

        self::assertSameSql(
            <<<SQL
            with "temp_table" ("foo", "bar", "baz") as (values (?, ?, ?))
            select *
            from "temp_table"
            SQL,
            $query
        );
    }

    public function testExpressionAsTableWithAliasInExpression()
    {
        $expression = new ConstantTable();
        $expression->row([1, 2, 3]);

        $query = new Select(new Aliased($expression, 'f.u.b.a.r.'));

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "f.u.b.a.r."
            SQL,
            $query
        );
    }

    public function testRange(): void
    {
        $query = new Select('some_table');

        $query->range(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 3',
            $query
        );
    }

    public function testRangeWithNegativeOffsetRaiseError(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/offset must be a positive integer/');

        $query->range(10, -1);
    }

    public function testRangeWithNegativeLimitRaiseError(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/limit must be a positive integer/');

        $query->range(-1, 10);
    }

    public function testPage(): void
    {
        $query = new Select('some_table');

        $query->page(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 20',
            $query
        );
    }

    public function testPageWith0RaiseError(): void
    {
        $query = new Select('some_table');

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/page must be a positive integer/');

        $query->page(10, 0);
    }

    public function testCastWithValue(): void
    {
        $query = new Select();
        $query->columnRaw(
            new Cast(12, 'some_type', 'value_type')
        );

        self::assertSameSql(
            'select cast(? as some_type)',
            $query
        );
    }

    public function testCastWithExpression(): void
    {
        $query = new Select();
        $query->columnRaw(
            new Cast(
                new Row(['bla', 12]),
                'some_type'
            )
        );

        self::assertSameSql(
            'select cast(row(?, ?) as some_type)',
            $query
        );
    }

    public function testCastWithExpressionInRow(): void
    {
        // This test is not necessary, but it correspond to a real life
        // use case, where the SQL server wrongly guess value types in
        // UNION query when one of the UNION queries is a constant table
        // expression (VALUES), just writing it for the posterity.
        $query = new Select('some_table', 'st');
        $query->columns(['st.a', 'st.b']);

        $other = new ConstantTable();
        $other->row([
            new Cast('60a696b1-e600-4c82-9ee4-4d56601a9120', 'uuid'),
            'foo'
        ]);

        $query->union($other);

        self::assertSameSql(
            <<<'SQL'
            select "st"."a", "st"."b" from "some_table" as "st"
            union
            values
                (cast(? as uuid), ?)
            SQL,
            $query
        );
    }

    public function testCastWithExpressionWarnValueTypeWillBeIgnored(): void
    {
        $query = new Select();
        $query->columnRaw(
            new Cast(
                new Row(['bla', 12]),
                'some_type',
                'value_type'
            )
        );

        self::assertSameSql(
            'select cast(row(?, ?) as some_type)',
            $query
        );

        self::markTestIncomplete("Warning is not implemented yet.");
    }

    public function testRawQueryGetsParsed(): void
    {
        $query = new Select('some_table');
        $query->columnRaw(new RawQuery('select ?::int', [1]));

        self::assertSameSql(
            'select (select ?) from "some_table"',
            $query
        );
    }

    public function testRawQueryWithExpressionArgumentAreExpanded(): void
    {
        $row = new Row([1, 'foo']);

        $query = new Select('some_table');
        $query->whereRaw(new RawQuery('(foo, bar) = ?', $row));

        self::assertSameSql(
            'select * from "some_table" where ((foo, bar) = (?, ?))',
            $query
        );
    }

    public function testRawGetsParsed(): void
    {
        $query = new Select('some_table');
        $query->columnRaw(new Raw('?::int', [1]));

        self::assertSameSql(
            'select ? from "some_table"',
            $query
        );
    }

    public function testRawWithExpressionArgumentAreExpanded(): void
    {
        $row = new Row([1, 'foo']);

        $query = new Select('some_table');
        $query->whereRaw(new Raw('(foo, bar) = ?', $row));

        self::assertSameSql(
            'select * from "some_table" where (foo, bar) = (?, ?)',
            $query
        );
    }

    public function testWith(): void
    {
        $query = (new Select('test1'))
            ->columnRaw('count(*)')
            ->join('test2', 'test1.a = test2.foo')
        ;

        $firstWith = (new Select('sometable'))->column('a');
        $query->with('test1', $firstWith);
        $secondWith = $query->createWith('test2', 'someothertable');
        $secondWith->column('foo');

        self::assertSameSql(
            <<<SQL
            with "test1" as (
                select "a" from "sometable"
            ), "test2" as (
                select "foo" from "someothertable"
            )
            select count(*)
            from "test1"
            inner join "test2"
                on (test1.a = test2.foo)
            SQL,
            $query
        );
    }

    public function testWhereInSelect(): void
    {
        $query = (new Select('test1'))
            ->column('foo')
            ->where('a',
                (new Select('test2'))
                  ->column('b')
            )
        ;

        self::assertSameSql(
            <<<SQL
            select "foo"
            from "test1"
            where
              "a" in (
                select "b"
                from "test2"
              )
            SQL,
            $query
        );
    }

    public function testFatQuery(): void
    {
        $writer = self::createTestWriter();

        $referenceArguments = [12, 3];

        $reference = <<<EOT
            select "t".*, "n"."type", count(n.id) as "comment_count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on (n.task_id = t.id)
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            order by
                "n"."type" asc,
                count(n.nid) desc
            limit 7 offset 42
            EOT;
        $countReference = <<<EOT
            select count(*) as "count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on (n.task_id = t.id)
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            EOT;

        // Compact way
        $query = new Select('task', 't');
        $query->column('t.*');
        $query->column('n.type');
        $query->column(new Raw('count(n.id)'), 'comment_count');
        // Add and remove a column for fun
        $query->column('some_field', 'some_alias')->removeColumn('some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy(new Raw('count(n.nid)'), Query::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->getWhere();
        $where->isEqual('t.user_id', 12);
        $where->isLess('t.deadline', new Raw('now()'));
        $having = $query->getHaving();
        $having->raw('count(n.nid) < ?', 3);

        $formatted = $writer->prepare($query);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $formatted = $writer->prepare($countQuery);
        self::assertSameSql($countReference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $clonedQuery = clone $query;
        $formatted = $writer->prepare($query);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        // We have to reset the reference because using a more buildish way we
        // do set precise where conditions on join conditions, and field names
        // get escaped
        $reference = <<<EOT
            select "t".*, "n"."type", count(n.id) as "comment_count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on ("n"."task_id" = "t"."id")
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            order by
                "n"."type" asc,
                count(n.nid) desc
            limit 7 offset 42
            EOT;
        $countReference = <<<EOT
            select count(*) as "count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on ("n"."task_id" = "t"."id")
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            EOT;

        // Builder way
        $query = (new Select('task', 't'))
            ->column('t.*')
            ->column('n.type')
            ->columnRaw('count(n.id)', 'comment_count')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderByRaw('count(n.nid)', Query::ORDER_DESC)
            ->range(7, 42)
        ;
        $query
            ->leftJoinWhere('task_note', 'n')
            ->isEqual('n.task_id', new ColumnName('t.id'))
        ;
        $where = $query->getWhere()
            ->isEqual('t.user_id', 12)
            ->isLess('t.deadline', new Raw('now()'))
        ;
        $having = $query->getHaving()
            ->raw('count(n.nid) < ?', 3)
        ;

        $formatted = $writer->prepare($query);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $formatted = $writer->prepare($countQuery);
        self::assertSameSql($countReference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $clonedQuery = clone $query;
        $formatted = $writer->prepare($clonedQuery);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        // Same without alias
        $reference = <<<EOT
            select "task".*, "task_note"."type", count(task_note.id) as "comment_count"
            from "task"
            left outer join "task_note"
                on (task_note.task_id = task.id)
            where
                "task"."user_id" = ?
                and task.deadline < now()
            group by
                "task"."id", "task_note"."type"
            having
                count(task_note.nid) < ?
            order by
                "task_note"."type" asc,
                count(task_note.nid) desc
            limit 7 offset 42
            EOT;
        $countReference = <<<EOT
            select count(*) as "count"
            from "task"
            left outer join "task_note"
                on (task_note.task_id = task.id)
            where
                "task"."user_id" = ?
                and task.deadline < now()
            group by
                "task"."id", "task_note"."type"
            having
                count(task_note.nid) < ?
            EOT;

        // Most basic way
        $query = (new Select('task'))
            ->column('task.*')
            ->column('task_note.type')
            ->columnRaw('count(task_note.id)', 'comment_count')
            ->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note')
            ->groupBy('task.id')
            ->groupBy('task_note.type')
            ->orderBy('task_note.type')
            ->orderByRaw('count(task_note.nid)', Query::ORDER_DESC)
            ->range(7, 42)
            ->where('task.user_id', 12)
            ->whereRaw('task.deadline < now()')
            ->havingRaw('count(task_note.nid) < ?', 3)
        ;

        $formatted = $writer->prepare($query);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $formatted = $writer->prepare($countQuery);
        self::assertSameSql($countReference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());

        $clonedQuery = clone $query;
        $formatted = $writer->prepare($clonedQuery);
        self::assertSameSql($reference, $formatted);
        self::assertSame($referenceArguments, $formatted->getArguments()->getAll());
    }
}
