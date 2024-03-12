<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLogItem;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyDrop;

/**
 * This code is generated using bin/generate_changes.php.
 *
 * Please do not modify it manually.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
abstract class GeneratedAbstractTransaction
{
    public function __construct(
        protected readonly string $database,
        protected readonly string $schema,
    ) {}

    /**
     * Add new arbitrary change.
     *
     * @internal
     *   For builders use only.
     */
    public abstract function logChange(ChangeLogItem $change): void;

    /**
     * Add a COLUMN.
     */
    public function addColumn(
        string $table,
        string $name,
        string $type,
        bool $nullable,
        null|string $default = null,
        null|string $collation = null,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ColumnAdd(
                table: $table,
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                collation: $collation,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Add a COLUMN.
     */
    public function modifyColumn(
        string $table,
        string $name,
        null|string $type = null,
        null|bool $nullable = null,
        null|string $default = null,
        bool $dropDefault = false,
        null|string $collation = null,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ColumnModify(
                table: $table,
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                dropDefault: $dropDefault,
                collation: $collation,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop a COLUMN.
     */
    public function dropColumn(
        string $table,
        string $name,
        bool $cascade = false,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ColumnDrop(
                table: $table,
                name: $name,
                cascade: $cascade,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Renames a COLUMN.
     */
    public function renameColumn(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ColumnRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop an arbitrary constraint from a table.
     */
    public function dropConstraint(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ConstraintDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Modify an arbitrary constraint on a table.
     */
    public function modifyConstraint(
        string $table,
        string $name,
        bool $deferrable = true,
        string $initially = ConstraintModify::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ConstraintModify(
                table: $table,
                name: $name,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Rename an arbitrary constraint.
     */
    public function renameConstraint(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ConstraintRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Add a FOREIGN KEY constraint on a table.
     */
    public function addForeignKey(
        string $table,
        array $columns,
        string $foreignTable,
        array $foreignColumns,
        null|string $name = null,
        null|string $foreignSchema = null,
        string $onDelete = ForeignKeyAdd::ON_DELETE_NO_ACTION,
        string $onUpdate = ForeignKeyAdd::ON_UPDATE_NO_ACTION,
        bool $deferrable = true,
        string $initially = ForeignKeyAdd::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ForeignKeyAdd(
                table: $table,
                name: $name,
                columns: $columns,
                foreignTable: $foreignTable,
                foreignColumns: $foreignColumns,
                foreignSchema: $foreignSchema,
                onDelete: $onDelete,
                onUpdate: $onUpdate,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Modify a FOREIGN KEY constraint on a table.
     */
    public function modifyForeignKey(
        string $table,
        string $name,
        string $onDelete = ForeignKeyModify::ON_DELETE_NO_ACTION,
        string $onUpdate = ForeignKeyModify::ON_UPDATE_NO_ACTION,
        bool $deferrable = true,
        string $initially = ForeignKeyModify::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ForeignKeyModify(
                table: $table,
                name: $name,
                onDelete: $onDelete,
                onUpdate: $onUpdate,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop a FOREIGN KEY constraint from a table.
     */
    public function dropForeignKey(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ForeignKeyDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Rename an arbitrary constraint.
     */
    public function renameForeignKey(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new ForeignKeyRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Create an INDEX on a table.
     */
    public function createIndex(
        string $table,
        array $columns,
        null|string $name = null,
        null|string $type = null,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new IndexCreate(
                table: $table,
                name: $name,
                columns: $columns,
                type: $type,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop an INDEX from a table.
     */
    public function dropIndex(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new IndexDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Rename an arbitrary constraint.
     */
    public function renameIndex(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new IndexRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Add the PRIMARY KEY constraint on a table.
     */
    public function addPrimaryKey(
        string $table,
        array $columns,
        null|string $name = null,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new PrimaryKeyAdd(
                table: $table,
                name: $name,
                columns: $columns,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop the PRIMARY KEY constraint from a table.
     */
    public function dropPrimaryKey(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new PrimaryKeyDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop a table.
     */
    public function dropTable(
        string $name,
        bool $cascade = false,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new TableDrop(
                name: $name,
                cascade: $cascade,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Renames a table.
     */
    public function renameTable(
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new TableRename(
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Create a UNIQUE constraint on a table.
     */
    public function addUniqueKey(
        string $table,
        array $columns,
        null|string $name = null,
        bool $nullsDistinct = true,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new UniqueKeyAdd(
                table: $table,
                name: $name,
                columns: $columns,
                nullsDistinct: $nullsDistinct,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }

    /**
     * Drop a UNIQUE constraint from a table.
     */
    public function dropUniqueKey(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->logChange(
            new UniqueKeyDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            )
        );

        return $this;
    }
}
