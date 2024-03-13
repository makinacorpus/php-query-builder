<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Escaper;

use MakinaCorpus\QueryBuilder\Escaper\Escaper;

/**
 * Almost SQL standard compliant escaper. Default for unit tests or when the
 * user didn't configure anything. It should mostly work with PostgreSQL.
 *
 * This implementation is unsafe to use with a real database server.
 *
 * Most implementations should extend this one and override escapeIdentifier(),
 * escapeLiteral(), getEscapeSequences() and writePlaceholder()
 * methods:
 *  - escapeIdentifier(), escapeLiteral(), getEscapeSequences()
 *    are server side dialect related methods,
 *  - writePlaceholder() and unescapePlaceholderChar() are PHP side driver
 *    related method.
 */
class StandardEscaper implements Escaper
{
    public function __construct(
        private string $placeholder = '?',
        private ?int $placeholderOffset = null,
    ) {}

    #[\Override]
    public function escapeIdentifier(string $string): string
    {
        // See https://www.postgresql.org/docs/10/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
        return '"' . \str_replace('"', '""', $string) . '"';
    }

    #[\Override]
    public function escapeLiteral(string $string): string
    {
        // See https://www.postgresql.org/docs/10/sql-syntax-lexical.html#SQL-SYNTAX-STRINGS
        //
        // PostgreSQL have different ways of escaping:
        //   - String constant, using single quote as a the delimiter, is an
        //     easy one since doubling all the quotes will work for escaping
        //     as long as you do not have dangling \ chars within.
        //   - String constant prefixed using E (eg. E'foo') will ignore the
        //     inside \ chars, and allows you to inject stuff such as \n.
        //   - String constants with unicodes escapes (we won't deal with it).
        //   - Dollar-quoted string constants (we won't deal with it).
        //
        // For convenience, default implementation will only naively handle
        // the first use case, because escaped C chars will already be
        // interpreted as the corresponding character by PHP, and what we get
        // is actually a binary UTF-8 string in most cases.
        //
        // Please be aware that in the end, the bridge will override this in
        // most case and this code will not be executed.
        return "'" . \str_replace("'", "''", $string) . "'";
    }

    /**
     * Get LIKE expression reserved characters.
     */
    protected function getReservedCharsLike(): string
    {
        return '\\%_';
    }

    #[\Override]
    public function escapeLike(string $string): string
    {
        return \addcslashes($string, $this->getReservedCharsLike());
    }

    /**
     * Get LIKE expression reserved characters.
     */
    protected function getReservedCharsSimilarTo(): string
    {
        return '\\%_|*+?{}()[]';
    }

    #[\Override]
    public function escapeSimilarto(string $string): string
    {
        return \addcslashes($string, $this->getReservedCharsSimilarTo());
    }

    #[\Override]
    public function getEscapeSequences(): array
    {
        return [
            '"',  // Identifier escape character
            '\'', // String literal escape character
            '$$', // String constant escape sequence
        ];
    }

    #[\Override]
    public function writePlaceholder(int $index): string
    {
        if ($this->placeholderOffset) {
            return $this->placeholder . ($this->placeholderOffset + $index);
        }
        return $this->placeholder;
    }

    #[\Override]
    public function unescapePlaceholderChar(): string
    {
        return '?';
    }
}
