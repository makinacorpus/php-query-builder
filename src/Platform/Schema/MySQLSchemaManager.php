<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\Column;
use MakinaCorpus\QueryBuilder\Schema\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\Key;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintDrop;


/**
 * Please note that some functions here might use information_schema tables
 * which are restricted in listings, they will show up only table information
 * the current user owns or has non-SELECT privileges onto.
 */
class MySQLSchemaManager extends SchemaManager
{
    #[\Override]
    public function supportsTransaction(): bool
    {
        return false;
    }

    #[\Override]
    public function supportsSchema(): bool
    {
        return false;
    }

    #[\Override]
    public function supportsUnsigned(): bool
    {
        return true;
    }

    #[\Override]
    public function listDatabases(): array
    {
        return $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SHOW DATABASES
                SQL
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    public function listSchemas(string $database): array
    {
        return ['public'];
    }

    #[\Override]
    public function listTables(string $database, string $schema = 'public'): array
    {
        if ('public' !== $schema) {
            return [];
        }

        return $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    table_name
                FROM information_schema.tables
                WHERE
                    table_schema = ?
                    AND table_type = 'BASE TABLE'
                ORDER BY table_name ASC
                SQL,
                [$database, $schema]
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    public function tableExists(string $database, string $name, string $schema = 'public'): bool
    {
        if ('public' !== $schema) {
            return false;
        }

        return (bool) $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    1
                FROM information_schema.tables
                WHERE
                    table_schema = ?
                    AND table_name = ?
                    AND table_type = 'BASE TABLE'
                ORDER BY table_name ASC
                SQL,
                [$database, $name]
            )
            ->fetchOne()
        ;
    }

    #[\Override]
    protected function getTableComment(string $database, string $name, string $schema = 'public'): ?string
    {
        return $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    table_comment
                FROM information_schema.tables
                WHERE
                    table_schema = ?
                    AND table_name = ?
                    AND table_type = 'BASE TABLE'
                SQL,
                [$schema, $name]
            )
            ->fetchOne()
        ;
    }

    #[\Override]
    protected function getTableColumns(string $database, string $name, string $schema = 'public'): array
    {
        /*
        $defaultCollation = $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT datcollate FROM pg_database WHERE datname = ?
                SQL,
                [$database]
            )
            ->fetchOne()
        ;
         */
        $defaultCollation = null; // @todo

        // @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-columns-table.html
        return $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    column_name,
                    data_type,
                    extra,
                    column_type,
                    is_nullable,
                    character_maximum_length,
                    numeric_precision,
                    numeric_scale,
                    collation_name,
                    column_default,
                    column_comment
                FROM information_schema.columns
                WHERE
                    table_schema = ?
                    AND table_name = ?
                ORDER BY ordinal_position ASC
                SQL,
                [$database, $name]
            )
            ->setHydrator(function (ResultRow $row) use ($defaultCollation, $database, $name) {
                $creationType = $row->get('column_type', 'string');

                return new Column(
                    collation: $row->get('collation_name', 'string') ?? $defaultCollation,
                    comment: $row->get('column_comment', 'string'),
                    database: $database,
                    length: $row->get('character_maximum_length', 'int'),
                    name: $row->get('column_name', 'string'),
                    nullabe: $row->get('is_nullable', 'string') !== 'NO',
                    options: [],
                    precision: $row->get('numeric_precision', 'int'),
                    scale: $row->get('numeric_scale', 'int'),
                    schema: 'public',
                    table: $name,
                    unsigned: \str_contains($creationType, 'unsigned'),
                    valueType: $row->get('data_type', 'string'),
                );
            })
            ->fetchAllHydrated()
        ;
    }

    #[\Override]
    protected function getTablePrimaryKey(string $database, string $name, string $schema = 'public'): ?Key
    {
        $primaryKeyColumns = $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    column_name
                FROM information_schema.key_column_usage
                WHERE
                    constraint_schema = ?
                    AND table_name = ?
                    AND constraint_name = 'PRIMARY'
                ORDER BY ordinal_position
                SQL,
                [$database, $name]
            )
            ->fetchFirstColumn()
        ;

        if ($primaryKeyColumns) {
            return new Key(
                columnNames: $primaryKeyColumns,
                comment: null, // @todo
                database: $database,
                name: 'PRIMARY',
                options: [],
                schema: 'public',
                table: $name,
            );
        }

        return null;
    }

    #[\Override]
    protected function getTableForeignKeys(string $database, string $name, string $schema = 'public'): array
    {
        return \array_values(\array_filter(
            $this->getAllTableKeysInfo($database, $name, $schema),
            fn (ForeignKey $key) => ($key->getTable() === $name && $key->getSchema() === $schema),
        ));
    }

    #[\Override]
    protected function getTableReverseForeignKeys(string $database, string $name, string $schema = 'public'): array
    {
        return \array_values(\array_filter(
            $this->getAllTableKeysInfo($database, $name, $schema),
            fn (ForeignKey $key) => ($key->getForeignTable() === $name && $key->getForeignSchema() === $schema),
        ));
    }

    /**
     * Get all table keys info.
     *
     * This SQL statement is terrible to maintain, so for maintainability,
     * we prefer to load all even when uncessary and filter out the result.
     *
     * Since this is querying the catalog, it will be fast no matter how
     * much result this yields.
     *
     * @return ForeignKey[]
     */
    private function getAllTableKeysInfo(string $database, string $name, string $schema = 'public'): array
    {
        $ret = [];

        $foreignKeyInfo = $this
            ->queryExecutor
            ->executeQuery(
                <<<SQL
                SELECT
                    constraint_name,
                    table_name,
                    referenced_table_name
                FROM information_schema.referential_constraints
                WHERE
                    constraint_schema = ?
                    AND (
                        table_name = ?
                        OR referenced_table_name = ?
                    )
                SQL,
                [$database, $name, $name]
            )
        ;

        while ($row = $foreignKeyInfo->fetchRow()) {
            $constraintName = $row->get('constraint_name', 'string');
            $constraintTable = $row->get('table_name', 'string');

            $keyColumns = $this->queryExecutor->executeQuery(
                <<<SQL
                SELECT column_name, referenced_column_name
                FROM information_schema.key_column_usage
                WHERE
                    constraint_schema = ?
                    AND table_name = ?
                    AND constraint_name = ?
                ORDER BY ordinal_position
                SQL,
                [$database, $constraintTable, $constraintName]
            )->fetchAllKeyValue();

            $ret[] = new ForeignKey(
                columnNames: \array_keys($keyColumns), 
                comment: null, // @todo
                database: $database,
                foreignColumnNames: \array_values($keyColumns),
                foreignSchema: 'public',
                foreignTable: $row->get('referenced_table_name', 'string'),
                name: $constraintName,
                options: [],
                schema: 'public',
                table: $constraintTable,
            );
        }

        return $ret;
    }

    #[\Override]
    protected function writeForeignKeySpec(ForeignKeyAdd $change): Expression
    {
        if ($change->isDeferrable()) {
            // @todo log that MySQL doesn't support deferring?
        }

        return $this->writeConstraintSpec(
            $change->getName(),
            $this->raw(
                'FOREIGN KEY (?::id[]) REFERENCES ?::table (?::id[])',
                [
                    $change->getColumns(),
                    $change->getForeignTable(),
                    $change->getForeignColumns(),
                ],
            ),
        );
    }

    #[\Override]
    protected function writeIndexCreate(IndexCreate $change): iterable|Expression
    {
        // MySQL requires a name.
        $name = $change->getName() ?? $change->generateName();

        return $this->raw('CREATE INDEX ?::id ON ? (?::id[])', [$name, $this->table($change), $change->getColumns()]);
    }

    #[\Override]
    protected function writeIndexDrop(IndexDrop $change): Expression
    {
        return $this->raw('DROP INDEX ?::id ON ?::table', [$change->getName(), $change->getTable()]);
    }

    #[\Override]
    protected function writeIndexRename(IndexRename $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ?::table RENAME INDEX ?::id TO ?::id', [$change->getTable(), $change->getName(), $change->getNewName()]);
    }

    #[\Override]
    protected function writePrimaryKeyDrop(PrimaryKeyDrop $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ?::table DROP PRIMARY KEY', [$change->getTable()]);
    }

    #[\Override]
    protected function writeUniqueConstraintAdd(UniqueConstraintAdd $change): Expression
    {
        if (!$change->isNullsDistinct()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }
        if ($name = $change->getName()) {
            return $this->raw('ALTER TABLE ?::table ADD UNIQUE ?::id (?::id[])', [$change->getTable(), $name, $change->getColumns()]);
        }
        return $this->raw('ALTER TABLE ?::table ADD UNIQUE (?::id[])', [$change->getTable(), $change->getColumns()]);
    }

    #[\Override]
    protected function writeUniqueConstraintDrop(UniqueConstraintDrop $change): Expression
    {
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
    }
}
