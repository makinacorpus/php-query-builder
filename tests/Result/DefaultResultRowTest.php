<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\QueryBuilder\Result\DefaultResultRow;
use MakinaCorpus\QueryBuilder\Error\ResultError;

class DefaultResultRowTest extends TestCase
{
    protected function createResultRow(): DefaultResultRow
    {
        return new DefaultResultRow([
            'a' => 'foo',
            'b' => 12,
            'c' => null,
        ]);
    }

    public function testGetWithIndex(): void
    {
        $row = $this->createResultRow();

        self::assertSame(12, $row->get(1));
    }

    public function testGetWithName(): void
    {
        $row = $this->createResultRow();

        self::assertSame(12, $row->get('b'));
    }

    public function testGetNull(): void
    {
        $row = $this->createResultRow();

        self::assertNull($row->get('c'));
    }

    public function testGetWithType(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testGetNullWithType(): void
    {
        $row = $this->createResultRow();

        self::assertNull($row->get('c'), 'some_type');
    }

    public function testGetWithIndexNegativeError(): void
    {
        $row = $this->createResultRow();

        self::expectException(ResultError::class);
        $row->get(-1);
    }

    public function testGetWithIndexPositiveError(): void
    {
        $row = $this->createResultRow();

        self::expectException(ResultError::class);
        $row->get(3);
    }

    public function testHasWithIndex(): void
    {
        $row = $this->createResultRow();

        self::assertTrue($row->has(1));
        self::assertFalse($row->has(3));
    }

    public function testHasWithName(): void
    {
        $row = $this->createResultRow();

        self::assertTrue($row->has('b'));
        self::assertFalse($row->has('d'));
    }

    public function testHasNull(): void
    {
        $row = $this->createResultRow();

        self::assertTrue($row->has('c'));
        self::assertFalse($row->has('d'));
    }

    public function testHasNullNotAllowed(): void
    {
        $row = $this->createResultRow();

        self::assertFalse($row->has('c', false));
        self::assertFalse($row->has('d', false));
    }

    public function testRawWithIndex(): void
    {
        $row = $this->createResultRow();

        self::assertSame(12, $row->raw(1));
    }

    public function testRawWithName(): void
    {
        $row = $this->createResultRow();

        self::assertSame(12, $row->raw('b'));
    }

    public function testRawWithIndexNegativeError(): void
    {
        $row = $this->createResultRow();

        self::expectException(ResultError::class);
        $row->raw(-1);
    }

    public function testRawWithIndexPositiveError(): void
    {
        $row = $this->createResultRow();

        self::expectException(ResultError::class);
        $row->raw(3);
    }

    public function testToArray(): void
    {
        $row = $this->createResultRow();

        self::assertSame(
            ['a' => 'foo', 'b' => 12, 'c' => null],
            $row->toArray(),
        );
    }
}
