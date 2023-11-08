<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Represents SIMILAR TO expression.
 */
class SimilarTo extends Comparison
{
    public function __construct(mixed $left, mixed $right)
    {
        parent::__construct(
            ExpressionHelper::column($left),
            \is_string($right) ? new SimilarToPattern($right) : ExpressionHelper::value($right),
            'similar to',
        );
    }
}
