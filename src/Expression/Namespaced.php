<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represent an identifier.
 */
class Namespaced implements Expression
{
    public function __construct(
        private ?string $namespace = null,
    ) {}

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }
}
