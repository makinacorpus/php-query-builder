<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

/**
 * Represent a column name.
 */
class ColumnName extends Identifier
{
    public function __construct(
        string $name,
        ?string $namespace = null,
        bool $noAutomaticNamespace = false,
    ) {
        if (!$noAutomaticNamespace && null === $namespace && \str_contains($name, '.')) {
            list($namespace, $name) = \explode('.', $name);
        }
        parent::__construct($name, $namespace);
    }
}
