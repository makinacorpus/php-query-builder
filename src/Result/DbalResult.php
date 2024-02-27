<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

/**
 * API compatible interface with doctrine/dbal result class.
 *
 * Some additional parameters are added on some methods.
 *
 * @see \Doctrine\DBAL\Result
 */
interface DbalResult
{
    /** @deprecated */
    public const ASSOCIATIVE = 2;

    /** @deprecated */
    public const NUMERIC = 3;

    /** @deprecated */
    public const COLUMN = 7;

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs.
     *
     * @deprecated
     *   Use {@see fetchNumeric()}, {@see fetchAssociative()} or {@see fetchOne()} instead.
     */
    public function fetch(int $mode = self::ASSOCIATIVE): mixed;

    /**
     * Get next element as an numerically indexed array.
     *
     * @return null|array<int,mixed>
     */
    public function fetchNumeric(): null|array;

    /**
     * Get next element as an associate array.
     *
     * @return null|array<string,mixed>
     */
    public function fetchAssociative(): null|array;

    /**
     * Fetch a single column value from the next row.
     *
     * @return mixed
     *   SQL value. Some drivers might already have converted it.
     */
    public function fetchOne(int|string $valueColumn = 0): mixed;

    /**
     * Returns an array containing all of the result rows represented as numeric arrays.
     *
     * @return array<int,array<int,mixed>>
     */
    public function fetchAllNumeric(): array;

    /**
     * Returns an array containing all of the result rows represented as associative arrays.
     *
     * @return array<int,array<string,mixed>>
     */
    public function fetchAllAssociative(): array;

    /**
     * Returns an array containing the values of the first column of the result.
     *
     * @return array<string,mixed>
     */
    public function fetchAllKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): array;

    /**
     * Returns an associative array with the keys mapped to the first column and the values being
     * an associative array representing the rest of the columns and their values.
     *
     * @return array<string,array<string,mixed>>
     */
    public function fetchAllAssociativeIndexed(int|string $keyColumn = 0): array;

    /**
     * Fetch first column
     *
     * @return array<int,mixed>
     */
    public function fetchFirstColumn(int|string $valueColumn = 0): array;

    /**
     * @return \Traversable<int,array<int,mixed>>
     */
    public function iterateNumeric(): \Traversable;

    /**
     * @return \Traversable<int,array<string,mixed>>
     */
    public function iterateAssociative(): \Traversable;

    /**
     * @return \Traversable<int,array<string,mixed>>
     */
    public function iterateKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): \Traversable;

    /**
     * Returns an iterator over the result set with the keys mapped to the
     * first column and the values being an associative array representing the
     * rest of the columns and their values.
     *
     * @return \Traversable<string,array<string,mixed>>
     */
    public function iterateAssociativeIndexed(int|string $keyColumn = 0): \Traversable;

    /**
     * @return \Traversable<int,mixed>
     */
    public function iterateColumn(int|string $valueColumn = 0): \Traversable;

    /**
     * Get row count.
     */
    public function rowCount(): int;

    /**
     * Get column count.
     */
    public function columnCount(): int;

    /**
     * Release current result. Any further attempt in manipulating
     * data will raise exceptions.
     */
    public function free(): void;
}
