<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\NullValue;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Query\Update;

class ExpressionHelperTest extends UnitTestCase
{
    public function testArgumentsReturnArray(): void
    {
        self::assertSame(
            ['foo'],
            ExpressionHelper::arguments(
                'foo'
            )
        );

        self::assertSame(
            ['foo'],
            ExpressionHelper::arguments(
                ['foo']
            )
        );

        self::assertSame(
            [],
            ExpressionHelper::arguments(
                []
            )
        );

        self::assertSame(
            [],
            ExpressionHelper::arguments(
                null
            )
        );
    }

    public function testJsonNull(): void
    {
        self::assertEquals(
            new NullValue(),
            ExpressionHelper::json(
                null
            )
        );
    }

    public function testJsonCallback(): void
    {
        self::assertEquals(
            new Raw('coucou'),
            ExpressionHelper::json(
                fn () => new Raw('coucou')
            )
        );
    }

    public function testJsonEmpty(): void
    {
        self::assertEquals(
            new Value([], 'jsonb'),
            ExpressionHelper::json('')
        );
    }

    public function testJsonExpression(): void
    {
        self::assertEquals(
            new Raw('hej'),
            ExpressionHelper::json(
                new Raw('hej')
            )
        );
    }

    public function testJsonNormal(): void
    {
        self::assertEquals(
            new Value(['hey' => 'ho'], 'jsonb'),
            ExpressionHelper::json(
                ['hey' => 'ho']
            )
        );
    }

    public function testArrayNull(): void
    {
        self::assertEquals(
            new NullValue(),
            ExpressionHelper::array(
                null
            )
        );
    }

    public function testArrayCallback(): void
    {
        self::assertEquals(
            new Raw('hello'),
            ExpressionHelper::array(
                fn () => new Raw('hello')
            )
        );
    }

    public function testArrayEmpty(): void
    {
        self::assertEquals(
            new ArrayValue([]),
            ExpressionHelper::array('')
        );
    }

    public function testArrayExpression(): void
    {
        self::assertEquals(
            new Raw('hola'),
            ExpressionHelper::array(
                new Raw('hola')
            )
        );
    }

    public function testArrayNormal(): void
    {
        self::assertEquals(
            new ArrayValue(['hey', 'ho']),
            ExpressionHelper::array(
                ['hey', 'ho']
            )
        );
    }

    public function testCallable(): void
    {
        self::assertSame(
            'bar',
            ExpressionHelper::callable(
                'bar'
            ),
        );

        self::assertSame(
            'fizz',
            ExpressionHelper::callable(
                fn () => 'fizz',
            ),
        );
    }

    public function testCallableDoesNotExecuteFunctionString(): void
    {
        self::assertSame(
            'time',
            ExpressionHelper::callable('time'),
        );
    }

    public function testColumnRaiseErroWhenNull(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::column(null);
    }

    public function testColumnRaiseErroWhenNoReturn(): void
    {
        self::expectExceptionMessageMatches('/Expression must return a value/');
        ExpressionHelper::column(new Update('foo'));
    }

    public function testColumnRaiseErroWhenEmpty(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::column('');
    }

    public function testColumnRaiseErroWhenExpressionIsStupid(): void
    {
        self::expectExceptionMessageMatches('/Column reference must be a string or an instance/');
        ExpressionHelper::column(new \DateTimeImmutable());
    }

    public function testColumnCallable(): void
    {
        self::assertEquals(
            new ColumnName('fizz'),
            ExpressionHelper::column(
                fn () => 'fizz',
            ),
        );
    }

    public function testColumnRaw(): void
    {
        self::assertEquals(
            new Raw('this is arbitrary'),
            ExpressionHelper::column(
                new Raw('this is arbitrary')
            ),
        );
    }

    public function testColumnString(): void
    {
        self::assertEquals(
            new ColumnName('fizz', 'buzz'),
            ExpressionHelper::column(
                'buzz.fizz'
            ),
        );
    }

    public function testTableRaiseErroWhenNull(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::table(null);
    }

    public function testTableRaiseErroWhenEmpty(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::table('');
    }

    public function testTableRaiseErroWhenNoReturn(): void
    {
        self::expectExceptionMessageMatches('/Expression must return a value/');
        ExpressionHelper::table(new Update('foo'));
    }

    public function testTableRaiseErroWhenExpressionIsStupid(): void
    {
        self::expectExceptionMessageMatches('/Table reference must be a string or an instance/');
        ExpressionHelper::table(new \DateTimeImmutable());
    }

    public function testTableCallable(): void
    {
        self::assertEquals(
            new TableName('fizz'),
            ExpressionHelper::table(
                fn () => 'fizz',
            ),
        );
    }

    public function testTableExpression(): void
    {
        self::assertEquals(
            new Raw('this is arbitrary'),
            ExpressionHelper::table(
                new Raw('this is arbitrary')
            ),
        );
    }

    public function testTableString(): void
    {
        self::assertEquals(
            new TableName('fizz', null, 'buzz'),
            ExpressionHelper::table(
                'buzz.fizz'
            ),
        );
    }

    public function testTableNameRaiseErroWhenNull(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::tableName(null);
    }

    public function testTableNameRaiseErroWhenEmpty(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::tableName('');
    }

    public function testTableNameRaiseErrorWhenNotTableName(): void
    {
        self::expectExceptionMessageMatches('/Table expression must be a/');
        ExpressionHelper::tableName(new Raw('foo'));
    }

    public function testTableNameTableName(): void
    {
        $expression = new TableName('fizz', null, 'buzz');

        self::assertSame(
            $expression,
            ExpressionHelper::tableName(
                $expression
            ),
        );
    }

    public function testTableNameString(): void
    {
        self::assertEquals(
            new TableName('fizz', null, 'buzz'),
            ExpressionHelper::tableName(
                'buzz.fizz'
            ),
        );
    }

    public function testValueNull(): void
    {
        self::assertEquals(
            new NullValue(),
            ExpressionHelper::value(null)
        );
    }

    public function testValueCallable(): void
    {
        self::assertEquals(
            new NullValue(),
            ExpressionHelper::value(
                fn () => null,
            )
        );

        self::assertEquals(
            new Value('some value'),
            ExpressionHelper::value(
                fn () => 'some value',
            )
        );

        self::assertEquals(
            new Raw('some raw expression'),
            ExpressionHelper::value(
                fn () => new Raw('some raw expression'),
            )
        );
    }

    public function testValueExpression(): void
    {
        self::assertEquals(
            new Raw('this is arbitrary'),
            ExpressionHelper::value(
                new Raw('this is arbitrary')
            ),
        );
    }

    public function testValueArray(): void
    {
        self::assertEquals(
            new Value(['foo', 'bar']),
            ExpressionHelper::value(
                ['foo', 'bar'],
            ),
        );
    }

    public function testValueArrayAsRow(): void
    {
        self::assertEquals(
            new Row(['foo', 'bar']),
            ExpressionHelper::value(
                ['foo', 'bar'],
                true,
            ),
        );
    }

    public function testRawRaiseErroWhenNull(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::raw(null);
    }

    public function testRawRaiseErroWhenEmpty(): void
    {
        self::expectExceptionMessageMatches('/Expression cannot be null or empty/');
        ExpressionHelper::raw('');
    }

    public function testRawRaiseErroWhenExpressionAndArguments(): void
    {
        self::expectExceptionMessageMatches('/You cannot call pass \$arguments/');
        ExpressionHelper::raw(new Raw('some expression'), ['some', 'arguments']);
    }

    public function testRawRaiseErroWhenExpressionIsStupid(): void
    {
        self::expectExceptionMessageMatches('/Raw expression must be a scalar or an instance/');
        ExpressionHelper::raw(new \DateTimeImmutable());
    }

    public function testRawCallable(): void
    {
        self::assertEquals(
            new Raw('oups, I did it', ['again']),
            ExpressionHelper::raw(
                fn () => 'oups, I did it',
                ['again']
            )
        );

        self::assertEquals(
            new Raw('dont push me to the edge'),
            ExpressionHelper::raw(
                fn () => new Raw('dont push me to the edge'),
            )
        );

        self::assertEquals(
            new Raw('I m trying not to lose my head'),
            ExpressionHelper::raw(
                new Raw('I m trying not to lose my head'),
            )
        );
    }
}
