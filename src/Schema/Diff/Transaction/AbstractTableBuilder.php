<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * @internal
 *   Exists because PHP has no genericity.
 */
abstract class AbstractTableBuilder
{
    /** @var ColumnAdd[] */
    private array $columns = [];
    private null|PrimaryKeyAdd $primaryKey = null;
    /** @var ForeignKeyAdd[] */
    private array $foreignKeys = [];
    /** @var UniqueKeyAdd[] */
    private array $uniqueKeys = [];
    /** @var IndexCreate[] */
    private array $indexes = [];
    private bool $temporary = false;

    public function __construct(
        private readonly AbstractSchemaTransaction $parent,
        private readonly string $name,
        private string $schema,
    ) {}

    /**
     * Set table schema.
     */
    public function schema(string $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Mark table as being temporary.
     */
    public function temporary(bool $temporary = true): static
    {
        $this->temporary = $temporary;

        return $this;
    }

    /**
     * Set the primary key.
     */
    public function column(string $name, string|Type $type, bool $nullable, ?string $default = null): static
    {
        $this->columns[] = new ColumnAdd(
            default: $default,
            name: $name,
            nullable: $nullable,
            schema: $this->schema,
            table: $this->name,
            type: $type,
        );

        return $this;
    }

    /**
     * Set the primary key.
     *
     * @param string[] $columns
     */
    public function primaryKey(array $columns): static
    {
        $this->primaryKey = new PrimaryKeyAdd(
            columns: $columns,
            schema: $this->schema,
            table: $this->name,
        );

        return $this;
    }

    /**
     * Add a foreign key.
     *
     * @param array<string,string> $columns
     *   Keys are local table columns, values are foreign table columns.
     */
    public function foreignKey(
        string $foreignTable,
        array $columns,
        ?string $name = null,
        ?string $foreignSchema = null,
        string $onDelete = ForeignKeyAdd::ON_DELETE_NO_ACTION,
        string $onUpdate = ForeignKeyAdd::ON_UPDATE_NO_ACTION,
        bool $deferrable = true,
        string $initially = ForeignKeyAdd::INITIALLY_DEFERRED,
    ): static {
        $this->foreignKeys[] = new ForeignKeyAdd(
            columns: \array_keys($columns),
            deferrable: $deferrable,
            foreignColumns: \array_values($columns),
            foreignSchema: $foreignSchema,
            foreignTable: $foreignTable,
            initially: $initially,
            name: $name,
            onDelete: $onDelete,
            onUpdate: $onUpdate,
            schema: $this->schema,
            table: $this->name,
        );

        return $this;
    }

    /**
     * Add a unique key.
     *
     * @param string[] $columns
     */
    public function uniqueKey(array $columns, ?string $name = null, bool $nullsDistinct = true): static
    {
        $this->uniqueKeys[] = new UniqueKeyAdd(
            columns: $columns,
            name: $name,
            nullsDistinct: $nullsDistinct,
            schema: $this->schema,
            table: $this->name,
        );

        return $this;
    }

    /**
     * Add an index.
     *
     * @param string[] $columns
     */
    public function index(array $columns, ?string $name = null, ?string $type = null): static
    {
        $this->indexes[] = new IndexCreate(
            columns: $columns,
            name: $name,
            schema: $this->schema,
            table: $this->name,
            type: $type,
        );

        return $this;
    }

    /**
     * Create and log table.
     */
    protected function createAndLogTable(): void
    {
        $this->parent->logChange(
            new TableCreate(
                columns: $this->columns,
                foreignKeys: $this->foreignKeys,
                indexes: $this->indexes,
                name: $this->name,
                primaryKey: $this->primaryKey,
                schema: $this->schema,
                temporary: $this->temporary,
                uniqueKeys: $this->uniqueKeys,
            ),
        );
    }
}
