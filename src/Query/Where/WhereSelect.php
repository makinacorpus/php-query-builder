<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Query\Select;

class WhereSelect extends Where
{
    public function __construct(
        private Select $parent,
        ?string $operator = null,
    ) {
        parent::__construct($operator);
    }

    public function end(): Select
    {
        return $this->parent;
    }

    #[\Override]
    public function nested(string $operator, mixed ...$expressions): WhereSelectNested
    {
        $nested = new WhereSelectNested($this, $operator);
        if ($expressions) {
            $nested->with(...$expressions);
        }

        return $this->children[] = $nested;
    }

    #[\Override]
    public function or(mixed ...$expressions): WhereSelectNested
    {
        return $this->nested(self::OR, ...$expressions);
    }

    #[\Override]
    public function and(mixed ...$expressions): WhereSelectNested
    {
        return $this->nested(self::AND, ...$expressions);
    }
}
