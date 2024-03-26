<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Condition;

use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLogItem;

/**
 * Condition on the schema that returns true or false.
 */
abstract class AbstractCondition implements ChangeLogItem
{
    public function __construct(
        private readonly string $schema,
        private readonly bool $negation = false,
    ) {}

    #[\Override]
    public function getSchema(): string
    {
        return $this->schema;
    }

    public function isNegation(): bool
    {
        return $this->negation;
    }
}
