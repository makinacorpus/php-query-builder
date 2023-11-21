<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

/**
 * Represent an arbitrary identifier.
 */
class Identifier extends Namespaced
{
    public function __construct(
        private string $name,
        ?string $namespace = null,
    ) {
        parent::__construct($namespace);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
