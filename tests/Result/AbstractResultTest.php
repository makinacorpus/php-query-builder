<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Error\ResultAlreadyStartedError;
use MakinaCorpus\QueryBuilder\Error\ResultError;
use MakinaCorpus\QueryBuilder\Error\ResultLockedError;
use MakinaCorpus\QueryBuilder\Result\ArrayResult;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use PHPUnit\Framework\TestCase;

class AbstractResultTest extends TestCase
{
    protected function createResult(): Result
    {
        return new ArrayResult([
            ['a' => 'key1', 'b' => 'val1'],
            ['a' => 'key2', 'b' => 'val2'],
        ]);
    }

    public function testTraverseWhenHydratorUsingResultRow(): void
    {
        $result = $this
            ->createResult()
            ->setHydrator(fn (ResultRow $row) => $row->get('a') . $row->get('b'))
        ;

        self::assertSame(
            [
                "key1val1",
                "key2val2",
            ],
            \iterator_to_array($result),
        );
    }

    public function testTraverseWhenHydratorUsingArray(): void
    {
        $result = $this
            ->createResult()
            ->setHydrator(fn (array $row) => $row['a'] . $row['b'])
        ;

        self::assertSame(
            [
                "key1val1",
                "key2val2",
            ],
            \iterator_to_array($result),
        );
    }

    public function testTraverseWithoutHydrator(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testSetColumnTypesWithInt(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testSetColumnTypesWithNames(): void
    {
        self::markTestIncomplete("Implement me.");
    }

    public function testFetchRow(): void
    {
        $result = $this->createResult();

        self::assertSame(['a' => "key1", 'b' => "val1"], $result->fetchRow()?->toArray());
        // @phpstan-ignore-next-line
        self::assertSame(['a' => "key2", 'b' => "val2"], $result->fetchRow()?->toArray());
        self::assertNull($result->fetchRow());
    }

    public function testFetchHydratedUsingResultRow(): void
    {
        $result = $this
            ->createResult()
            ->setHydrator(fn (ResultRow $row) => $row->get('a') . $row->get('b'))
        ;

        self::assertSame("key1val1", $result->fetchHydrated());
        self::assertSame("key2val2", $result->fetchHydrated());
        self::assertNull($result->fetchRow());
    }

    public function testFetchHydratedUsingArray(): void
    {
        $result = $this
            ->createResult()
            ->setHydrator(fn (array $row) => $row['a'] . $row['b'])
        ;

        self::assertSame("key1val1", $result->fetchHydrated());
        self::assertSame("key2val2", $result->fetchHydrated());
        self::assertNull($result->fetchRow());
    }

    public function testFetchHydratedErrorWhenNoHydratorSet(): void
    {
        $result = $this->createResult();
        $result->free();

        self::expectException(ResultError::class);
        $result->fetchHydrated();
    }

    public function testFetchHydratedErrorWhenFree(): void
    {
        $result = $this->createResult();
        $result->setHydrator(fn () => null);
        $result->free();

        self::expectException(ResultLockedError::class);
        $result->fetchHydrated();
    }

    public function testFetchNumeric(): void
    {
        $result = $this->createResult();

        self::assertSame(["key1", "val1"], $result->fetchNumeric());
        self::assertSame(["key2", "val2"], $result->fetchNumeric());
        self::assertNull($result->fetchNumeric());
    }

    public function testFetchNumericErrorWhenFree(): void
    {
        $result = $this->createResult();
        $result->free();

        self::expectException(ResultLockedError::class);
        $result->fetchNumeric();
    }

    public function testFetchAssociative(): void
    {
        $result = $this->createResult();

        self::assertSame(['a' => "key1", 'b' => "val1"], $result->fetchAssociative());
        self::assertSame(['a' => "key2", 'b' => "val2"], $result->fetchAssociative());
        self::assertNull($result->fetchAssociative());
    }

    public function testFetchAssociativeErrorWhenFree(): void
    {
        $result = $this->createResult();
        $result->free();

        self::expectException(ResultLockedError::class);
        $result->fetchAssociative();
    }

    public function testFetchOne(): void
    {
        $result = $this->createResult();

        self::assertSame("key1", $result->fetchOne());
        self::assertSame("val2", $result->fetchOne('b'));
        self::assertNull($result->fetchOne());
    }

    public function testFetchOneErrorWhenFree(): void
    {
        $result = $this->createResult();
        $result->free();

        self::expectException(ResultLockedError::class);
        $result->fetchOne();
    }

    public function testFetchAllNumeric(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                ["key1", "val1"],
                ["key2", "val2"],
            ],
            $result->fetchAllNumeric(),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllNumeric();
    }

    public function testFetchAllNumericErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllNumeric();
    }

    public function testFetchAllAssociative(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                ['a' => "key1", 'b' => "val1"],
                ['a' => "key2", 'b' => "val2"],
            ],
            $result->fetchAllAssociative(),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociative();
    }

    public function testFetchAllAssociativeErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociative();
    }

    public function testFetchAllKeyValue(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "key1" => "val1",
                "key2" => "val2",
            ],
            $result->fetchAllKeyValue(),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllKeyValue();
    }

    public function testFetchAllKeyValueWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "val1" => "key1",
                "val2" => "key2",
            ],
            $result->fetchAllKeyValue(1, 0),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllKeyValue();
    }

    public function testFetchAllKeyValueWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "val1" => "key1",
                "val2" => "key2",
            ],
            $result->fetchAllKeyValue('b', 'a'),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllKeyValue();
    }

    public function testFetchAllKeyValueErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllKeyValue();
    }

    public function testFetchAllAssociativeIndexed(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'key1' => ['a' => "key1", 'b' => "val1"],
                'key2' => ['a' => "key2", 'b' => "val2"],
            ],
            $result->fetchAllAssociativeIndexed(),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociativeIndexed();
    }

    public function testFetchAllAssociativeIndexedWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'val1' => ['a' => "key1", 'b' => "val1"],
                'val2' => ['a' => "key2", 'b' => "val2"],
            ],
            $result->fetchAllAssociativeIndexed(1),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociativeIndexed();
    }

    public function testFetchAllAssociativeIndexedWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'val1' => ['a' => "key1", 'b' => "val1"],
                'val2' => ['a' => "key2", 'b' => "val2"],
            ],
            $result->fetchAllAssociativeIndexed('b'),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociativeIndexed();
    }

    public function testFetchAllAssociativeIndexedErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchAllAssociativeIndexed();
    }

    public function testFetchFirstColumn(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['key1', 'key2'],
            $result->fetchFirstColumn(),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchFirstColumn();
    }

    public function testFetchFirstColumnWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['val1', 'val2'],
            $result->fetchFirstColumn(1),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchFirstColumn();
    }

    public function testFetchFirstColumnWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['val1', 'val2'],
            $result->fetchFirstColumn('b'),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchFirstColumn();
    }

    public function testFetchFirstColumnErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->fetchFirstColumn();
    }

    public function testIterateNumeric(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                ["key1", "val1"],
                ["key2", "val2"],
            ],
            \iterator_to_array($result->iterateNumeric()),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateNumeric();
    }

    public function testIterateNumericErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateNumeric();
    }

    public function testIterateAssociative(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                ['a' => "key1", 'b' => "val1"],
                ['a' => "key2", 'b' => "val2"],
            ],
            \iterator_to_array($result->iterateAssociative()),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociative();
    }

    public function testIterateAssociativeErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociative();
    }

    public function testIterateKeyValue(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "key1" => "val1",
                "key2" => "val2",
            ],
            \iterator_to_array($result->iterateKeyValue()),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateKeyValue();
    }

    public function testIterateKeyValueWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "val1" => "key1",
                "val2" => "key2",
            ],
            \iterator_to_array($result->iterateKeyValue(1, 0)),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateKeyValue();
    }

    public function testIterateKeyValueWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                "val1" => "key1",
                "val2" => "key2",
            ],
            \iterator_to_array($result->iterateKeyValue('b', 'a')),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateKeyValue();
    }

    public function testIterateKeyValueErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateKeyValue();
    }

    public function testIterateAssociativeIndexed(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'key1' => ['a' => "key1", 'b' => "val1"],
                'key2' => ['a' => "key2", 'b' => "val2"],
            ],
            \iterator_to_array($result->iterateAssociativeIndexed()),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociativeIndexed();
    }

    public function testIterateAssociativeIndexedWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'val1' => ['a' => "key1", 'b' => "val1"],
                'val2' => ['a' => "key2", 'b' => "val2"],
            ],
            \iterator_to_array($result->iterateAssociativeIndexed(1)),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociativeIndexed();
    }

    public function testIterateAssociativeIndexedWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            [
                'val1' => ['a' => "key1", 'b' => "val1"],
                'val2' => ['a' => "key2", 'b' => "val2"],
            ],
            \iterator_to_array($result->iterateAssociativeIndexed('b')),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociativeIndexed();
    }

    public function testIterateAssociativeIndexedErrorWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateAssociativeIndexed();
    }

    public function testIterateColumn(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['key1', 'key2'],
            \iterator_to_array($result->iterateColumn()),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateColumn();
    }

    public function testIterateColumnWithColumnIndex(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['val1', 'val2'],
            \iterator_to_array($result->iterateColumn(1)),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateColumn();
    }

    public function testIterateColumnWithColumnName(): void
    {
        $result = $this->createResult();

        self::assertSame(
            ['val1', 'val2'],
            \iterator_to_array($result->iterateColumn('b')),
        );

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateColumn();
    }

    public function testIterateColumnWhenStarted(): void
    {
        $result = $this->createResult();
        $result->fetchOne();

        self::expectException(ResultAlreadyStartedError::class);
        $result->iterateColumn();
    }
}
