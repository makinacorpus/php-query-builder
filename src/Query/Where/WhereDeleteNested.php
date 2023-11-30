<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

class WhereDeleteNested extends WhereWhere
{
    public function __construct(
        private WhereDelete $parent,
        ?string $operator = null,
    ) {
        parent::__construct($this, $operator);
    }

    public function end(): WhereDelete
    {
        return $this->parent;
    }
}
