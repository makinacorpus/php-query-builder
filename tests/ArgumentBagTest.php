<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\ArgumentBag;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use PHPUnit\Framework\TestCase;

class ArgumentBagTest extends TestCase
{
    public function testAddAll(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'some_type');

        self::assertSame('some_type', $bag->getTypeAt(0));
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
        self::assertSame('value_type', $bag->getTypeAt(0));
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

        self::assertSame('int', $bag->getTypeAt(0));
        self::assertNull($bag->getTypeAt(1));
        self::assertSame('int', $bag->getTypeAt(2));

        $bag->setTypeAt(1, 'string');

        self::assertSame('int', $bag->getTypeAt(0));
        self::assertSame('string', $bag->getTypeAt(1));
        self::assertSame('int', $bag->getTypeAt(2));

        $bag->setTypeAt(3, 'foo');
        self::assertSame('foo', $bag->getTypeAt(3));
        self::assertNull($bag->getAll()[3]);
    }

    public function testGetTypes(): void
    {
        $bag = new ArgumentBag();
        $bag->add(1, 'int');
        $bag->add(2, 'string');

        self::assertSame(['int', 'string'], $bag->getTypes());
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
