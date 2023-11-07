<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

/**
 * SQL CONCAT().
 */
class Concat extends FunctionCall
{
    public function __construct(mixed ...$arguments)
    {
        parent::__construct('concat', ...$arguments);
    }
}
