<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Cast expression is the same as a value expression, but type is mandatory.
 *
 * It will propagate the cast to generated SQL, and thus enforce the SQL to
 * proceed to the CAST explicitely.
 *
 * You may also provide a value type, for the PHP side, in order for the
 * converter to proceed to a different conversion than the SQL cast.
 */
class Cast implements Castable
{
    private Expression $expression;
    private Type $castToType;

    public function __construct(
        mixed $expression,
        string|Type $castToType,
        null|string|Type $valueType = null
    ) {
        $this->castToType = Type::create($castToType);

        if ($expression instanceof Expression) {
            if ($valueType) {
                // @todo Raise warning: value type will be ignored
            }
        } else {
            $expression = new Value($expression, $valueType);
        }

        $this->expression = $expression;
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::create($this->castToType);
    }

    #[\Override]
    public function getCastToType(): ?Type
    {
        return $this->castToType;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }
}
