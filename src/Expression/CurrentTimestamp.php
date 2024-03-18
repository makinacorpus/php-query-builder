<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

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
    public function returnType(): ?Type
    {
        return Type::timestamp(true);
    }
}
