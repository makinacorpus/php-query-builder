<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

/**
 * SQL writer will use this to add alias in a safe manner.
 */
interface WithAlias
{
    /**
     * Get expression alias.
     */
    public function getAlias(): ?string;

    /**
     * Clone this with a new alias.
     */
    public function cloneWithAlias(?string $alias): static;
}
