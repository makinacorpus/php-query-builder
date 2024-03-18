<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

interface Castable extends Expression
{
    /**
     * Get cast value type.
     */
    public function getCastToType(): ?Type;
}
