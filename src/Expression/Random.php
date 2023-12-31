<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Non SQL standard RANDOM() expression.
 *
 * Generates a random number between 0 and 1, for most RDBMS.
 *
 * Result is not guaranted, in opposition to RandomInt().
 *
 * For example, SQLite will return a number between -9223372036854775808 and
 * +9223372036854775807 instead of 0 and 1. This expression can still be used
 * for random sorting.
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

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return 'numeric';
    }
}
