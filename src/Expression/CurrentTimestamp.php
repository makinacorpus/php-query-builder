<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * CURRENT_TIMESTAMP, NOW(), GETDATE() depending upon the dialect.
 */
class CurrentTimestamp implements Expression
{
    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?string
    {
        return 'timestamp';
    }
}
