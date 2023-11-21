<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Represent an identifier.
 */
class Namespaced implements Expression
{
    public function __construct(
        private ?string $namespace = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }
}
