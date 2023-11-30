<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

class WhereWhereNested extends WhereWhere
{
    public function __construct(
        private WhereWhere $parent,
        ?string $operator = null,
    ) {
        parent::__construct($this, $operator);
    }

    public function end(): WhereWhere
    {
        return $this->parent;
    }
}
