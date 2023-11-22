<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Represents LIKE / ILIKE expression.
 */
class Like extends Comparison
{
    public function __construct(
        mixed $left,
        mixed $right,
        private bool $caseSensitive = true
    ) {
        parent::__construct(
            ExpressionHelper::column($left),
            \is_string($right) ? new SimilarToPattern($right) : ExpressionHelper::value($right),
            $caseSensitive ? 'like' : 'ilike',
        );
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }
}
