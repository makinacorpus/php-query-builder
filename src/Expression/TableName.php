<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\TableExpression;
use MakinaCorpus\QueryBuilder\Type\Type;

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
            list($namespace, $name) = \explode('.', $name);
        }
        parent::__construct($name, $namespace);
    }

    #[\Override]
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    #[\Override]
    public function cloneWithAlias(?string $alias): static
    {
        $ret = clone $this;
        $ret->alias = $alias;

        return $ret;
    }
}
