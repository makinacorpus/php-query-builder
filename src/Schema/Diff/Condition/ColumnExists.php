<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Condition;

class ColumnExists extends AbstractCondition
{
    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $column,
        bool $negation = false,
    ) {
        parent::__construct($schema, $negation);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumn(): string
    {
        return $this->column;
    }
}
