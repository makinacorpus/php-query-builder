<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Read;

class Key extends AbstractObject
{
    public function __construct(
        string $database,
        string $name,
        string $table,
        ?string $comment,
        string $schema,
        array $options,
        /** @var string[] */
        private readonly array $columnNames
    ) {
        parent::__construct(
            comment: $comment,
            database: $database,
            name: $name,
            options: $options,
            namespace: $table,
            schema: $schema,
            type: ObjectId::TYPE_KEY,
        );
    }

    /**
     * Get table name.
     */
    public function getTable(): string
    {
        return $this->getNamespace();
    }

    /**
     * Get column names.
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * Does this key contains the given column.
     */
    public function contains(string $columnName): bool
    {
        return \in_array($columnName, $this->columnNames);
    }
}
