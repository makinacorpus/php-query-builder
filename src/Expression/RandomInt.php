<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Non SQL standard random integer expression.
 *
 * Generates a random number between given min and max values.
 *
 * Implementation will use CAST(RANDOM() * (? - ? + 1) + ? AS int) where
 * RANDOM() uses the Random expression class.
 */
class RandomInt implements Expression
{
    public function __construct(
        private int $max,
        private int $min = 0,
    ) {}

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::int();
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function getMax(): int
    {
        return $this->max;
    }
}
