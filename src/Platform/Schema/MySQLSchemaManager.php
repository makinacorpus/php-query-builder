<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\Column;
use MakinaCorpus\QueryBuilder\Schema\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Schema\Table;
use MakinaCorpus\QueryBuilder\Schema\Key;

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
    public function getTable(string $database, string $name, string $schema = 'public'): Table
    {
        if (!$this->tableExists($database, $name, $schema)) {
            throw new QueryBuilderError(\sprintf("Table '%s.%s.%s' does not exist", $database, $schema, $name));
        }

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
        $columns = $this
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
                    vendorId: null,
                );
            })
            ->fetchAllHydrated()
        ;

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

        $foreignKeys = [];
        $reverseForeignKeys = [];
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

            $key = new ForeignKey(
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
                vendorId: null,
            );
            if ($key->getTable() === $name) {
                $foreignKeys[] = $key;
            } else {
                $reverseForeignKeys[] = $key;
            }
        }

        $primaryKey = null;
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
            $primaryKey = new Key(
                columnNames: $primaryKeyColumns,
                comment: null, // @todo
                database: $database,
                name: 'PRIMARY',
                options: [],
                schema: 'public',
                table: $name,
                vendorId: null,
            );
        }

        $result = $this
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
            ->fetchRow()
        ;

        return new Table(
            columns: $columns,
            comment: $result?->get('table_comment', 'string'),
            database: $database,
            foreignKeys: $foreignKeys,
            name: $name,
            options: [],
            primaryKey: $primaryKey,
            reverseForeignKeys: $reverseForeignKeys,
            schema: $schema,
            vendorId: null,
        );
    }
}
