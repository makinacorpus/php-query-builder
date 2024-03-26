<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Condition;

use MakinaCorpus\QueryBuilder\QueryBuilder;

// @todo IDE bug.
\class_exists(QueryBuilder::class);

class CallbackCondition extends AbstractCondition
{
    private \Closure $callback;

    public function __construct(
        string $schema,
        /** @param (callable(QueryBuilder):bool) $callback */
        callable $callback,
        bool $negation = false,
    ) {
        parent::__construct($schema, $negation);

        $this->callback = $callback(...);
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
