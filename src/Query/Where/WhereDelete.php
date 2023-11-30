<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Query\Delete;

class WhereDelete extends Where
{
    public function __construct(
        private Delete $parent,
        ?string $operator = null,
    ) {
        parent::__construct($operator);
    }

    public function end(): Delete
    {
        return $this->parent;
    }

    #[\Override]
    public function nested(string $operator, mixed ...$expressions): WhereDeleteNested
    {
        $nested = new WhereDeleteNested($this, $operator);
        if ($expressions) {
            $nested->with(...$expressions);
        }

        return $this->children[] = $nested;
    }

    #[\Override]
    public function or(mixed ...$expressions): WhereDeleteNested
    {
        return $this->nested(self::OR, ...$expressions);
    }

    #[\Override]
    public function and(mixed ...$expressions): WhereDeleteNested
    {
        return $this->nested(self::AND, ...$expressions);
    }
}
