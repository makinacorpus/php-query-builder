<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Cast expression is the same as a value expression, but type is mandatory.
 *
 * It will propagate the cast to generated SQL, and thus enforce the SQL to
 * proceed to the CAST explicitely.
 *
 * You may also provide a value type, for the PHP side, in order for the
 * converter to proceed to a different conversion than the SQL cast.
 */
class Cast implements Expression
{
    private Expression $expression;

    public function __construct(
        mixed $expression,
        private string $castToType,
        ?string $valueType = null
    ) {
        if ($expression instanceof Expression) {
            if ($valueType) {
                // @todo Raise warning: value type will be ignored
            }
        } else {
            $expression = new Value($expression, $valueType);
        }

        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return $this->castToType;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * Get value type.
     */
    public function getCastToType(): string
    {
        return $this->castToType;
    }
}
