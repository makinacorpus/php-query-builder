<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

/**
 * @internal
 *   Exists because PHP has no genericity.
 */
class DeepNestedSchemaTransaction extends AbstractNestedSchemaTransaction
{
    public function __construct(
        private readonly NestedSchemaTransaction $parent,
        string $schema,
        array $conditions = [],
    ) {
        parent::__construct($schema, $conditions);
    }

    /**
     * Create a table builder.
     */
    public function createTable(string $name): DeepNestedTableBuilder
    {
        return new DeepNestedTableBuilder(parent: $this, name: $name, schema: $this->schema);
    }

    /**
     * End nested branch and go back to parent.
     */
    public function endIf(): NestedSchemaTransaction
    {
        return $this->parent;
    }
}
