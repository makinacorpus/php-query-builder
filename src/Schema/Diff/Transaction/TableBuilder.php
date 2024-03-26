<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;

class TableBuilder extends AbstractTableBuilder
{
    public function __construct(
        private readonly SchemaTransaction $parent,
        string $name,
        string $schema,
    ) {
        parent::__construct($parent, $name, $schema);
    }

    /**
     * Table is done.
     */
    public function endTable(): SchemaTransaction
    {
        $this->createAndLogTable();

        return $this->parent;
    }
}
