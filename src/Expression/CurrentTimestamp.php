<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * CURRENT_TIMESTAMP, NOw(), GETDATE() depending upon the dialect.
 */
class CurrentTimestamp implements Expression
{
    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }
}
