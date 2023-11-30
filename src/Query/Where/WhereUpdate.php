<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Where;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Query\Update;

class WhereUpdate extends Where
{
    public function __construct(
        private Update $parent,
        ?string $operator = null,
    ) {
        parent::__construct($operator);
    }

    public function end(): Update
    {
        return $this->parent;
    }

    #[\Override]
    public function nested(string $operator, mixed ...$expressions): WhereUpdateNested
    {
        $nested = new WhereUpdateNested($this, $operator);
        if ($expressions) {
            $nested->with(...$expressions);
        }

        return $this->children[] = $nested;
    }

    #[\Override]
    public function or(mixed ...$expressions): WhereUpdateNested
    {
        return $this->nested(self::OR, ...$expressions);
    }

    #[\Override]
    public function and(mixed ...$expressions): WhereUpdateNested
    {
        return $this->nested(self::AND, ...$expressions);
    }
}
