<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;

class NestedSchemaTransaction extends AbstractNestedSchemaTransaction
{
    public function __construct(
        private readonly SchemaTransaction $parent,
        string $database,
        string $schema,
        array $conditions = [],
    ) {
        parent::__construct($database, $schema, $conditions);
    }

    /**
     * Create a table builder.
     */
    public function createTable(string $name): NestedTableBuilder
    {
        return new NestedTableBuilder(parent: $this, database: $this->database, name: $name, schema: $this->schema);
    }

    /**
     * End nested branch and go back to parent.
     */
    public function endIf(): SchemaTransaction
    {
        return $this->parent;
    }
}
