<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\ArgumentBag;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Type\Type;

class ArgumentBagTest extends UnitTestCase
{
    public function testAddAll(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'some_type');

        self::assertSame('some_type', $bag->getTypeAt(0)?->name);
    }

    public function testAddWithoutType(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1);

        self::assertNull($bag->getTypeAt(0));
    }

    public function testAddValueExpression(): void
    {
        $bag = new ArgumentBag();
        $bag->add(new Value(2, 'value_type'));

        self::assertSame(2, $bag->getAll()[0]);
        self::assertSame('value_type', $bag->getTypeAt(0)?->name);
    }

    public function testAddExpressionRaiseError(): void
    {
        $bag = new ArgumentBag();

        self::expectExceptionMessageMatches('/Value cannot be an.*instance/');
        $bag->add(new Raw('foo'));
    }

    public function testGetSetTypeAt(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'int');
        $bag->add(2);
        $bag->add(2, 'int');
        $bag->add(3, Type::text());

        self::assertSameType(Type::int(), $bag->getTypeAt(0));
        self::assertNull($bag->getTypeAt(1));
        self::assertSameType(Type::int(), $bag->getTypeAt(2));
        self::assertSameType(Type::text(), $bag->getTypeAt(3));

        $bag->setTypeAt(1, 'string');

        self::assertSameType(Type::int(), $bag->getTypeAt(0));
        self::assertSameType(Type::text(), $bag->getTypeAt(1));
        self::assertSameType(Type::int(), $bag->getTypeAt(2));

        $bag->setTypeAt(5, 'foo');
        self::assertSame('foo', $bag->getTypeAt(5)?->name);
        self::assertNull($bag->getAll()[4]);
    }

    public function testGetTypes(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'int');
        $bag->add(2, 'string');

        self::assertSameType('int', $bag->getTypes()[0]);
        self::assertSameType('text', $bag->getTypes()[1]);
    }

    public function testCount(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'int');
        $bag->add(2, 'string');

        self::assertSame(2, $bag->count());

        $bag->addAll([3, 4]);

        self::assertSame(4, $bag->count());
    }
}
