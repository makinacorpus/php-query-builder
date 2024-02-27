<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

/**
 * When in use using the iterator, default behavior is to return associative
 * arrays.
 *
 * Both of the count() and countRows() method are aliases to each other. Some
 * drivers such as SQLite will always return 0 no matter how many rows there
 * is. You cannot rely on counting rows for portable code.
 *
 * Iterating over this result will use the fetch() method which uses the user
 * given hydrator to hydrate objects, if given, or returns ResultRow instances.
 */
interface Result extends DbalResult, \Traversable
{
    /**
     * Activate internal row cache in order to allow this iterator
     * to be rewindable. By doing this, you will consume much more memory.
     *
     * @internal
     *   Not implemented yet. Be warned.
     */
    public function setRewindable(bool $rewindable = true): static;

    /**
     * Some drivers are unable to count rows such as SQLite.
     */
    public function isCountReliable(): bool;

    /**
     * Set hydrator.
     *
     * @param callable $hydrator
     *   Two variants of the callable signature exists:
     *    - fn (ResultRow $row, ConverterContext $context) which is recommended.
     *    - fn (array $row) which is deprecated and support will be removed.
     *
     * @return $this
     */
    public function setHydrator(callable $hydrator): static;

    /**
     * Set a single column type.
     */
    public function setColumnType(int|string $index, string $phpType): static;

    /**
     * Set type map for faster hydration.
     *
     * @param array<string,string> $userTypes
     *   Keys are result column names, values are types.
     */
    public function setColumnTypes(array $userTypes): static;

    /**
     * Get next element as a ResultRow instance.
     */
    public function fetchRow(): ?ResultRow;

    /**
     * Get next element and move forward.
     *
     * This is the default method applied when iterating over the result
     * instance directly.
     *
     * @return mixed|ResultRow
     *   Either a ResultRow instance, or anything the hydrator will return
     *   if an user hydrator is set.
     */
    public function fetchHydrated(): mixed;

    /**
     * Fetch all items hydrated.
     *
     * @return array<mixed>|array<ResultRow>
     *   Either an array of ResultRow instances, or an array of anything the
     *   hydrator will return if an user hydrator is set.
     */
    public function fetchAllHydrated(): array;
}
