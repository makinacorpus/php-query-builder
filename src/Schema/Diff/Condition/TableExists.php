<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Condition;

class TableExists extends AbstractCondition
{
    public function __construct(
        string $schema,
        private readonly string $table,
        bool $negation = false,
    ) {
        parent::__construct($schema, $negation);
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
