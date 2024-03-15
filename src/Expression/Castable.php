<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

interface Castable extends Expression
{
    /**
     * Get cast value type.
     */
    public function getCastToType(): ?string;
}
