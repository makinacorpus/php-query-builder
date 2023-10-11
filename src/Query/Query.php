<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\Expression;

interface Query extends Expression
{
    const JOIN_INNER = 'outer';
    const JOIN_LEFT = 'left';
    const JOIN_LEFT_OUTER = 'left outer';
    const JOIN_RIGHT = 'right';
    const JOIN_RIGHT_OUTER = 'right outer';
    const JOIN_NATURAL = 'natural';
    const NULL_FIRST = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const CONFLICT_IGNORE = 1;
    const CONFLICT_UPDATE = 2;

    /**
     * Get query identifier
     *
     * @see Query::setIdentifier()
     */
    public function getIdentifier(): ?string;

    /**
     * @todo rewrite me.
     *
     * Set query unique identifier
     *
     * This identifier will serve two different purpose:
     *
     *   - if prepared, it will be the server side identifier of the prepared
     *     query, which allows you to call it more than once,
     *
     *   - if your backend is slow to fetch metadata, and marked as such, it
     *     will also serve the purpose of storing SQL query metadata, such as
     *     return types and column names and index mapping.
     *
     * In real life, each time you ask for a column type, no matter the database
     * driver in use under, fetching metadata will implicitely do SQL queries to
     * the server to ask for each column type.
     *
     * ext-pgsql driver has the courtesy of storing those in cache, which makes
     * it very efficient, skipping most of the round trips, but PDO will do as
     * much SQL query as the number of SQL query you'll run multiplied by the
     * number of returned columns.
     *
     * If your built queries are not dynamic, please always set an identifier.
     */
    public function setIdentifier(string $identifier): static;

    /**
     * Set a single query options
     *
     * null value means reset to default.
     */
    public function setOption(string $name, $value): static;

    /**
     * Set all options from
     *
     * null value means reset to default.
     */
    public function setOptions(array $options): static;

    /**
     * Get normalized options.
     */
    public function getOptions(null|string|array $overrides = null): array;

    /**
     * Should this query return something
     *
     * For INSERT, MERGE, UPDATE or DELETE queries without a RETURNING clause
     * this should return false, same goes for PostgresSQL PERFORM.
     *
     * Note that SELECT queries might also be run with a PERFORM returning
     * nothing, for example in some cases with FOR UPDATE.
     *
     * This may trigger some optimizations, for example with PDO this will
     * force the RETURN_AFFECTED behavior.
     */
    public function willReturnRows(): bool;
}
