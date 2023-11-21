<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Escaper;

/**
 * Escaper holds two different responsabilities, but they cannot be divided:
 *
 *   - some escaping functions can be wrote in plain PHP and will depend upon
 *     the database server and version, in other words, the platform,
 *
 *   - some escaping functions will depend upon the connection library, in other
 *     words, PDO, ext-pgsql, ...,
 *
 *   - which escaping function is concerned by which of the above statements
 *     will depend upon the low level connection library.
 *
 * Each bridge will need to implement its own.
 */
interface Escaper
{
    /**
     * Escape identifier (ie. table name, variable name, ...).
     */
    public function escapeIdentifier(string $string): string;

    /**
     * Escape literal (string).
     */
    public function escapeLiteral(string $string): string;

    /**
     * Escape like (string).
     */
    public function escapeLike(string $string): string;

    /**
     * Escape similar to (string).
     */
    public function escapeSimilarTo(string $string): string;

    /**
     * Get backend escape sequences.
     *
     * Escape sequences are only used by the SQL-rewriting method that proceed
     * to parameters cast and replacement.
     *
     * @return string[]
     */
    public function getEscapeSequences(): array;

    /**
     * Get the default anonymous placeholder for queries.
     *
     * @param int $index
     *   The numerical index position of the placeholder value.
     *
     * @return string
     *   The placeholder.
     */
    public function writePlaceholder(int $index): string;

    /**
     * The SQL writer uses "?" as the placholder parameter for user-given
     * values in queries.
     *
     * In order for this to work, during query formatting, it will escape
     * arbitrary "?" values to "??" in order to avoid confusion, then restore
     * it later prior to execution.
     *
     * This method should do nothing but simply return "?", ie. put back the
     * original "?" character in the SQL query, except for PDO which requires
     * that all "?" to be doubled as "??" otherwise it will attempt a value
     * replacement in places where it should not.
     *
     * For PDO, return "??", and all others just "?".
     */
    public function unescapePlaceholderChar(): string;
}
