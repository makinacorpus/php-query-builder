<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represents LIKE / SIMILAR TO pattern expression.
 */
class LikePattern implements Expression
{
    /**
     * Constructor.
     *
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, or an expression
     *   instance.
     * @param ?string $value
     *   If you need to use a string which contains reserved caracters in the
     *   pattern and need to be escaped, put the string here. The next parameter
     *   is the wildcard string that will be replaced in $pattern with this
     *   escaped value.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     */
    public function __construct(
        private string $pattern,
        private ?string $value = null,
        private ?string $wildcard = null,
    ) {
        if ($value) {
            if (!$wildcard) {
                throw new QueryBuilderError("You provided a value, you need to set a wildcard for replacement.");
            }
            if (!\str_contains($pattern, $wildcard)) {
                throw new QueryBuilderError("You provided a value but wildcard could not be found in pattern.");
            }
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::text();
    }

    public function hasValue(): bool
    {
        return null !== $this->value;
    }

    public function getUnsafeValue(): ?string
    {
        return $this->value;
    }

    public function getPattern(?string $escapedValue = null): string
    {
        return null === $escapedValue ? $this->pattern : \str_replace($this->wildcard, $escapedValue, $this->pattern);
    }
}
