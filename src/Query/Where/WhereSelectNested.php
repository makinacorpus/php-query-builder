<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

class WhereSelectNested extends WhereWhere
{
    public function __construct(
        private WhereSelect $parent,
        ?string $operator = null,
    ) {
        parent::__construct($this, $operator);
    }

    public function end(): WhereSelect
    {
        return $this->parent;
    }
}
