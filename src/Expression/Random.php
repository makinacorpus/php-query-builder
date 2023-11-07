<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Non SQL standard RANDOM() expression.
 *
 * Generates a random number between 0 and 1.
 *
 * PostgreSQL RANDOM() function will be applied, MySQL RAND() will be applied.
 * Other implementations may exist later.
 */
class Random implements Expression
{
    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }
}
