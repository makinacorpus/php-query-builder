<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

/**
 * @internal
 *   Exists because PHP has no genericity.
 */
class NestedTableBuilder extends AbstractTableBuilder
{
    public function __construct(
        private readonly NestedSchemaTransaction $parent,
        string $database,
        string $name,
        string $schema,
    ) {
        parent::__construct($parent, $database, $name, $schema);
    }

    /**
     * Table is done.
     */
    public function endTable(): NestedSchemaTransaction
    {
        $this->createAndLogTable();

        return $this->parent;
    }
}
