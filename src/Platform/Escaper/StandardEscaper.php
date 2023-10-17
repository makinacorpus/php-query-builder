<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Escaper;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
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

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        // See https://www.postgresql.org/docs/10/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
        return '"' . \str_replace('"', '""', $string) . '"';
    }

    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings): string
    {
        if (!$strings) {
            throw new QueryBuilderError("Cannot not format an empty identifier list.");
        }
        if (\is_iterable($strings)) {
            $values = [];
            foreach ($strings as $string) {
                $values[] = $this->escapeIdentifier($string);
            }
            return \implode(', ', $values);
        }
        return $this->escapeIdentifier($strings);
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function escapeLike(string $string, ?string $reservedChars = null): string
    {
        return \addcslashes($string, $reservedChars ?? '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '"',  // Identifier escape character
            '\'', // String literal escape character
            '$$', // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index): string
    {
        if ($this->placeholderOffset) {
            return $this->placeholder . ($this->placeholderOffset + $index);
        }
        return $this->placeholder;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapePlaceholderChar(): string
    {
        return '?';
    }
}
