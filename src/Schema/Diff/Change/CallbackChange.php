<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\QueryBuilder;

// @todo IDE bug.
\class_exists(QueryBuilder::class);

class CallbackChange extends AbstractChange
{
    private \Closure $callback;

    public function __construct(
        string $schema,
        /**
         * @param (callable(QueryBuilder):mixed) $callback
         *   Callback result will be ignored.
         */
        callable $callback,
    ) {
        parent::__construct($schema);

        $this->callback = $callback(...);
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
