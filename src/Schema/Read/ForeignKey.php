<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Read;

final class ForeignKey extends AbstractObject
{
    public function __construct(
        string $database,
        string $name,
        string $table,
        ?string $comment,
        string $schema,
        array $options,
        /** @var string[] */
        private readonly array $columnNames,
        private readonly string $foreignSchema,
        private readonly string $foreignTable,
        /** @var string[] */
        private readonly array $foreignColumnNames
    ) {
        parent::__construct(
            comment: $comment,
            database: $database,
            name: $name,
            options: $options,
            namespace: $table,
            schema: $schema,
            type: ObjectId::TYPE_FOREIGN_KEY,
        );
    }

    /**
     * Get source table that references the other one.
     */
    public function getTable(): string
    {
        return $this->getNamespace();
    }

    /**
     * Get columns in the source table.
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * Get referenced table schema.
     */
    public function getForeignSchema(): string
    {
        return $this->foreignSchema;
    }

    /**
     * Get referenced table.
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     * Get referenced table column names.
     */
    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }

    /**
     * Does this key contains the given column.
     */
    public function contains(string $columnName): bool
    {
        return \in_array($columnName, $this->columnNames);
    }
}
