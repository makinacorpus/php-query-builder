<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
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
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintDrop;

/**
 * This code is generated using bin/generate_changes.php.
 *
 * Please do not modify it manually.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class SchemaTransaction
{
    private ChangeLog $changeLog;

    public function __construct(
        private readonly SchemaManager $schemaManager,
        private readonly string $database,
        private readonly string $schema,
        private readonly \Closure $onCommit,
    ) {
        $this->changeLog = new ChangeLog($schemaManager);
    }
    
    public function commit(): void
    {
        ($this->onCommit)($this->changeLog->diff());
    }

    public function columnAdd(
        string $table,
        string $name,
        string $type,
        bool $nullable,
        null|string $default = null,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ColumnAdd(
                table: $table,
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function columnModify(
        string $table,
        string $name,
        string $type,
        bool $nullable,
        null|string $default = null,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ColumnModify(
                table: $table,
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function columnDrop(
        string $table,
        string $name,
        bool $cascade = false,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ColumnDrop(
                table: $table,
                name: $name,
                cascade: $cascade,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function columnRename(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ColumnRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function constraintDrop(
        string $name,
        string $table,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ConstraintDrop(
                name: $name,
                table: $table,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function constraintModify(
        string $name,
        string $table,
        bool $deferrable = true,
        string $initially = ConstraintModify::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ConstraintModify(
                name: $name,
                table: $table,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function constraintRename(
        string $name,
        string $table,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ConstraintRename(
                name: $name,
                table: $table,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function foreignKeyAdd(
        string $table,
        array $columns,
        string $foreignTable,
        array $foreignColumns,
        null|string $foreignSchema = null,
        null|string $name = null,
        string $onDelete = ForeignKeyAdd::ON_DELETE_NO_ACTION,
        string $onUpdate = ForeignKeyAdd::ON_UPDATE_NO_ACTION,
        bool $deferrable = true,
        string $initially = ForeignKeyAdd::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ForeignKeyAdd(
                table: $table,
                columns: $columns,
                foreignTable: $foreignTable,
                foreignColumns: $foreignColumns,
                foreignSchema: $foreignSchema,
                name: $name,
                onDelete: $onDelete,
                onUpdate: $onUpdate,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function foreignKeyModify(
        string $table,
        string $onDelete = ForeignKeyModify::ON_DELETE_NO_ACTION,
        string $onUpdate = ForeignKeyModify::ON_UPDATE_NO_ACTION,
        bool $deferrable = true,
        string $initially = ForeignKeyModify::INITIALLY_DEFERRED,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ForeignKeyModify(
                table: $table,
                onDelete: $onDelete,
                onUpdate: $onUpdate,
                deferrable: $deferrable,
                initially: $initially,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function foreignKeyDrop(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ForeignKeyDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function foreignKeyRename(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new ForeignKeyRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function indexCreate(
        string $table,
        array $columns,
        null|string $name = null,
        null|string $type = null,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new IndexCreate(
                table: $table,
                columns: $columns,
                name: $name,
                type: $type,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function indexDrop(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new IndexDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function indexRename(
        string $table,
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new IndexRename(
                table: $table,
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function primaryKeyAdd(
        string $table,
        array $columns,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new PrimaryKeyAdd(
                table: $table,
                columns: $columns,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function primaryKeyDrop(
        string $table,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new PrimaryKeyDrop(
                table: $table,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function tableCreate(
        string $name,
        array $columns = [],
        null|PrimaryKeyAdd $primaryKey = null,
        array $foreignKeys = [],
        array $uniqueKeys = [],
        array $indexes = [],
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new TableCreate(
                name: $name,
                columns: $columns,
                primaryKey: $primaryKey,
                foreignKeys: $foreignKeys,
                uniqueKeys: $uniqueKeys,
                indexes: $indexes,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function tableDrop(
        string $name,
        bool $cascade = false,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new TableDrop(
                name: $name,
                cascade: $cascade,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function tableRename(
        string $name,
        string $newName,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new TableRename(
                name: $name,
                newName: $newName,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function uniqueConstraintAdd(
        string $table,
        array $columns,
        null|string $name = null,
        bool $nullsDistinct = false,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new UniqueConstraintAdd(
                table: $table,
                columns: $columns,
                name: $name,
                nullsDistinct: $nullsDistinct,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

    public function uniqueConstraintDrop(
        string $table,
        string $name,
        ?string $schema = null,
    ): static {
        $this->changeLog->add(
            new UniqueConstraintDrop(
                table: $table,
                name: $name,
                schema: $schema ?? $this->schema,
                database: $this->database,
            ),
        );

        return $this;
    }

}