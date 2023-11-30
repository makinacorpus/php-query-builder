<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

use MakinaCorpus\QueryBuilder\Where;

class WhereWhere extends Where
{
    public function __construct(
        private Where $parent,
        ?string $operator = null,
    ) {
        parent::__construct($operator);
    }

    public function end(): Where
    {
        return $this->parent;
    }

    #[\Override]
    public function nested(string $operator, mixed ...$expressions): WhereWhereNested
    {
        $nested = new WhereWhereNested($this, $operator);
        if ($expressions) {
            $nested->with(...$expressions);
        }

        return $this->children[] = $nested;
    }

    #[\Override]
    public function or(mixed ...$expressions): WhereWhereNested
    {
        return $this->nested(self::OR, ...$expressions);
    }

    #[\Override]
    public function and(mixed ...$expressions): WhereWhereNested
    {
        return $this->nested(self::AND, ...$expressions);
    }
}
