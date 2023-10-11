<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Simply NULL.
 */
class NullValue implements Expression
{
    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }
}
