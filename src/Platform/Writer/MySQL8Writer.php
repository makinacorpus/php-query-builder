<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Raw;

/**
 * MySQL >= 8.
 */
class MySQL8Writer extends MySQLWriter
{
    /**
     * Format excluded item from INSERT or MERGE values.
     */
    protected function doFormatInsertExcludedItem($expression): Expression
    {
        if (\is_string($expression)) {
            // Let pass strings with dot inside, it might already been formatted.
            if (false !== \strpos($expression, ".")) {
                return new Raw($expression);
            }
            return new Raw("new." . $this->escaper->escapeIdentifier($expression));
        }

        return $expression;
    }
}
