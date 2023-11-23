<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\TableExpression;

/**
 * Represent a table name.
 */
class TableName extends Identifier implements TableExpression, WithAlias
{
    public function __construct(
        string $name,
        private ?string $alias = null,
        ?string $namespace = null,
        bool $noAutomaticNamespace = false,
    ) {
        if (!$noAutomaticNamespace && null === $namespace && \str_contains($name, '.')) {
            list ($namespace, $name) = \explode('.', $name);
        }
        parent::__construct($name, $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function cloneWithAlias(?string $alias): static
    {
        $ret = clone $this;
        $ret->alias = $alias;

        return $ret;
    }
}
