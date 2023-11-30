<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

class WhereUpdateNested extends WhereWhere
{
    public function __construct(
        private WhereUpdate $parent,
        ?string $operator = null,
    ) {
        parent::__construct($this, $operator);
    }

    public function end(): WhereUpdate
    {
        return $this->parent;
    }
}
