<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

/**
 * Represents LIKE/ILIKE/SIMILAR TO expression.
 */
class SimilarTo implements Expression
{
    const LIKE_RESERVED_CHARS = '\\%_';
    const SIMILAR_TO_RESERVED_CHARS = '\\%_|*+?{}()[]';

    private Expression $column;

    /**
     * Constructor.
     *
     * @param callable|string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with % and _ inside, and  for value.
     * @param ?string $value
     *   If you need to use a string which contains reserved caracters in the
     *   pattern and need to be escaped, put the string here. The next parameter
     *   is the wildcard string that will be replaced in $pattern with this
     *   escaped value.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     * @param bool $regex
     *   Set this to true if you wish to execute a SIMILAR TO instead of LIKE.
     * @param bool $caseInsensitive
     *   Will only have an effect when using LIKE, default is case sensitive.
     */
    public function __construct(
        mixed $column,
        private string $pattern,
        private ?string $value = null,
        private ?string $wildcard = null,
        private bool $regex = false,
        private bool $caseInsensitive = false,
    ) {
        $this->column = ExpressionHelper::column($column);

        if ($value) {
            if (!$wildcard) {
                throw new QueryBuilderError("You provided a value, you need to set a wildcard for replacement.");
            }
            if (!\str_contains($pattern, $wildcard)) {
                throw new QueryBuilderError("You provided a value but wildcard could not be found in pattern.");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    public function getReservedChars(): string
    {
        return $this->regex ? self::SIMILAR_TO_RESERVED_CHARS : self::LIKE_RESERVED_CHARS;
    }

    public function getColumn(): Expression
    {
        return $this->column;
    }

    public function hasValue(): bool
    {
        return null !== $this->value;
    }

    public function getUnsafeValue(): ?string
    {
        return $this->value;
    }

    public function isRegex(): bool
    {
        return $this->regex;
    }

    public function isCaseSensitive(): bool
    {
        return !$this->caseInsensitive;
    }

    public function getPattern(?string $escapedValue = null): string
    {
        return null === $escapedValue ? $this->pattern : \str_replace($this->wildcard, $escapedValue, $this->pattern);
    }
}
