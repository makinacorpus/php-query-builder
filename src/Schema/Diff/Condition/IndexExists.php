<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Condition;

class IndexExists extends AbstractCondition
{
    public function __construct(
        string $schema,
        private readonly string $table,
        /** @var string[] */
        private readonly array $columns,
        bool $negation = false,
    ) {
        parent::__construct($schema, $negation);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /** @return string[] */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
