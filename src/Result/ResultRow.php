<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

/**
 * Single row ow fetched from a result iterator.
 */
interface ResultRow extends \ArrayAccess
{
    /**
     * Fetch and an hydrated value using given PHP type.
     *
     * PHP type can be either a class name or a scalar type name. Converter
     * will be triggered and find the appropriate SQL to PHP converter
     * depending upon the RDBMS given type name and the user given PHP type
     * name.
     *
     * Specifying the PHP type is the only way to make your code type safe
     * It may also make hydration faster in certain cases.
     *
     * @return null|mixed
     *   The converted value if a type is given, raw SQL value otherwise.
     */
    public function get(int|string $columnName, ?string $phpType = null, bool $caseSensitive = false): mixed;

    /**
     * Get a nested sub-row, useful if you return ROW() or composite types
     * from within your SELECT or other returning query.
     *
     * This will work with all of:
     *
     *  - An arbitrary nested ROW(),
     *  - An arbitrary JSON structure,
     *  - An arbitrary ARRAY of values.
     *
     * All other types will raised an exception.
     *
     * Please note that with nested ROW() and ARRAY of values, you will not
     * have any string keys, but numerically indexed set of values instead.
     *
     * When using a JSON structure as return, you will get an instance with
     * named properties.
     */
    // public function nested(int|string $columnName): ResultRow;

    /**
     * Does the value exists.
     */
    public function has(int|string $columnName, bool $allowNull = true, bool $caseSensitive = false): bool;

    /**
     * Get the raw unconverted value from SQL result.
     */
    public function raw(int|string $columnName, bool $caseSensitive = false): null|int|float|string;

    /**
     * Return raw values as array.
     *
     * @return array<string,null|string>
     *   Keys are column names, values are SQL raw string values.
     */
    public function toArray(): array;
}
