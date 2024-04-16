<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Read\Column;
use MakinaCorpus\QueryBuilder\Schema\Read\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\Read\Index;
use MakinaCorpus\QueryBuilder\Schema\Read\Key;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Please note that some functions here might use information_schema tables
 * which are restricted in listings, they will show up only table information
 * the current user owns or has non-SELECT privileges onto.
 */
class PostgreSQLSchemaManager extends SchemaManager
{
    #[\Override]
    public function supportsTransaction(): bool
    {
        return true;
    }

    #[\Override]
    public function listDatabases(): array
    {
        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT datname
                FROM pg_database
                ORDER BY datname ASC
                SQL
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    public function listSchemas(?string $database = null): array
    {
        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    schema_name
                FROM information_schema.schemata
                WHERE
                    catalog_name = ?
                    AND schema_name NOT LIKE 'pg\_%'
                    AND schema_name != 'information_schema'
                ORDER BY schema_name ASC
                SQL,
                [$database ?? $this->session->getCurrentDatabase()]
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    protected function doListTables(string $database, string $schema): array
    {
        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    quote_ident(table_name) AS table_name
                FROM information_schema.tables
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name <> 'geometry_columns'
                    AND table_name <> 'spatial_ref_sys'
                    AND table_type <> 'VIEW'
                ORDER BY table_name ASC
                SQL,
                [$database, $schema]
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    protected function doTableExists(string $database, string $schema, string $name): bool
    {
        return (bool) $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    true
                FROM information_schema.tables
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name = ?
                    AND table_type <> 'VIEW'
                SQL,
                [$database, $schema, $name]
            )
            ->fetchOne()
        ;
    }

    #[\Override]
    protected function getTableComment(string $database, string $schema, string $name): ?string
    {
        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    description
                FROM pg_description
                WHERE objoid = (
                    SELECT oid FROM pg_class
                    WHERE
                        relnamespace = to_regnamespace(?)
                        AND oid = to_regclass(?)
                )
                SQL,
                [$schema, $name]
            )
            ->fetchOne()
        ;
    }

    #[\Override]
    protected function getTableColumns(string $database, string $schema, string $name): array
    {
        $defaultCollation = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT datcollate FROM pg_database WHERE datname = ?
                SQL,
                [$database]
            )
            ->fetchOne()
        ;

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    column_name,
                    data_type,
                    udt_name,
                    is_nullable,
                    character_maximum_length,
                    numeric_precision,
                    numeric_scale,
                    collation_name,
                    column_default
                    -- comment
                FROM information_schema.columns
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name = ?
                ORDER BY ordinal_position ASC
                SQL,
                [$database, $schema, $name]
            )
            ->setHydrator(fn (ResultRow $row) => new Column(
                collation: $row->get('collation_name', 'string') ?? $defaultCollation,
                comment: null, // @todo,
                database: $database,
                name: $row->get('column_name', 'string'),
                nullabe: $row->get('is_nullable', 'string') !== 'NO',
                options: [],
                schema: $schema,
                table: $name,
                // @todo Build Type directly from SQL create string.
                valueType: new Type(
                    array: false, // @todo
                    internal: InternalType::fromTypeName($row->get('udt_name', 'string')),
                    length: $row->get('character_maximum_length', 'int'),
                    name: $row->get('udt_name', 'string'),
                    precision: $row->get('numeric_precision', 'int'),
                    scale: $row->get('numeric_scale', 'int'),
                    unsigned: false,
                    withTimeZone: false, // @todo
                ),
            ))
            ->fetchAllHydrated()
        ;
    }

    #[\Override]
    protected function getTablePrimaryKey(string $database, string $schema, string $name): ?Key
    {
        $result = $this->getAllTableKeysInfo($database, $schema, $name);

        while ($row = $result->fetchRow()) {
            if ($row->get('type') === 'p') {
                return new Key(
                    columnNames: $row->get('column_source', 'string[]'),
                    comment: null, // @todo
                    database: $database,
                    name: $row->get('name', 'string'),
                    options: [],
                    schema: $row->get('table_source_schema', 'string'),
                    table: $row->get('table_source', 'string'),
                );
            }
        }

        return null;
    }

    #[\Override]
    protected function getTableForeignKeys(string $database, string $schema, string $name): array
    {
        $ret = [];
        $result = $this->getAllTableKeysInfo($database, $schema, $name);

        while ($row = $result->fetchRow()) {
            if ($row->get('type') === 'f' && $row->get('table_source', 'string') === $name) {
                $ret[] = new ForeignKey(
                    columnNames: $row->get('column_source', 'string[]'),
                    comment: null, // @todo
                    database: $database,
                    foreignColumnNames: $row->get('column_target', 'string[]'),
                    foreignSchema: $row->get('table_target_schema', 'string'),
                    foreignTable: $row->get('table_target', 'string'),
                    name: $row->get('name', 'string'),
                    options: [],
                    schema: $row->get('table_source_schema', 'string'),
                    table: $row->get('table_source', 'string'),
                );
            }
        }

        return $ret;
    }

    #[\Override]
    protected function getTableReverseForeignKeys(string $database, string $schema, string $name): array
    {
        $ret = [];
        $result = $this->getAllTableKeysInfo($database, $schema, $name);

        while ($row = $result->fetchRow()) {
            if ($row->get('type') === 'f' && $row->get('table_source', 'string') !== $name) {
                $ret[] = new ForeignKey(
                    columnNames: $row->get('column_source', 'string[]'),
                    comment: null, // @todo
                    database: $database,
                    foreignColumnNames: $row->get('column_target', 'string[]'),
                    foreignSchema: $row->get('table_target_schema', 'string'),
                    foreignTable: $row->get('table_target', 'string'),
                    name: $row->get('name', 'string'),
                    options: [],
                    schema: $row->get('table_source_schema', 'string'),
                    table: $row->get('table_source', 'string'),
                );
            }
        }

        return $ret;
    }

    /**
     * Get all table keys info.
     *
     * This SQL statement is terrible to maintain, so for maintainability,
     * we prefer to load all even when uncessary and filter out the result.
     *
     * Since this is querying the catalog, it will be fast no matter how
     * much result this yields.
     */
    private function getAllTableKeysInfo(string $database, string $schema, string $name): Result
    {
        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    con.conname AS name,
                    class_src.relname AS table_source,
                    (
                        SELECT nspname
                        FROM pg_catalog.pg_namespace
                        WHERE
                            oid = class_src.relnamespace
                    ) AS table_source_schema,
                    class_tgt.relname AS table_target,
                    (
                        SELECT nspname
                        FROM pg_catalog.pg_namespace
                        WHERE
                            oid = class_tgt.relnamespace
                    ) AS table_target_schema,
                    (
                        SELECT array_agg(attname)
                        FROM pg_catalog.pg_attribute
                        WHERE
                            attrelid = con.conrelid
                            AND attnum IN (SELECT * FROM unnest(con.conkey))
                    ) AS column_source,
                    (
                        SELECT array_agg(attname)
                        FROM pg_catalog.pg_attribute
                        WHERE
                            attrelid = con.confrelid
                            AND attnum IN (SELECT * FROM unnest(con.confkey))
                    ) AS column_target,
                    con.contype AS type
                FROM pg_catalog.pg_constraint con
                JOIN pg_catalog.pg_class class_src
                    ON class_src.oid = con.conrelid
                LEFT JOIN pg_catalog.pg_class class_tgt
                    ON class_tgt.oid = con.confrelid
                WHERE
                    con.contype IN ('f', 'p')
                    AND con.connamespace =  to_regnamespace(?)
                    AND (
                        con.conrelid =  to_regclass(?)
                        OR con.confrelid =  to_regclass(?)
                    )
                SQL,
                [$schema, $name, $name]
            )
        ;
    }

    #[\Override]
    protected function getTableIndexes(string $database, string $schema, string $name): array
    {
        $ret = [];

        // pg_index.indkey is an int2vector, this a deprecated type that must
        // not be used in pgsql. There are no functions to convert it to array
        // and it simply return a space-separated list of values we need then
        // to explode in order to extract column numbers. This is the only way
        // in the catalog to get indexes keys *in order*.
        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    i.relname AS index_name,
                    (
                        SELECT array_agg(attname)
                        FROM (
                            SELECT a.attname
                            FROM (
                                SELECT colnum, row_number() over() AS rownum
                                FROM (
                                    SELECT unnest(string_to_array(ix.indkey::text, ' ')) AS colnum
                                ) AS c
                            ) AS b, pg_attribute a
                            WHERE a.attrelid = t.oid AND a.attnum = b.colnum::int
                            ORDER BY b.rownum
                        ) AS d
                    ) AS column_names
                FROM pg_catalog.pg_class t
                JOIN pg_catalog.pg_index ix ON ix.indrelid = t.oid
                JOIN pg_catalog.pg_class i ON i.oid = ix.indexrelid
                WHERE
                    t.relkind = 'r'
                    AND t.relnamespace = to_regnamespace(?)
                    AND t.relname = ?
                SQL,
                [$schema, $name]
            )
        ;

        while ($row = $result->fetchRow()) {
            $ret[] = new Index(
                columnNames: $row->get('column_names', 'string[]'),
                comment: null, // @todo
                database: $database,
                name: $row->get('index_name', 'string'),
                options: [],
                schema: $schema,
                table: $name,
            );
        }

        return $ret;
    }

    #[\Override]
    protected function doWriteColumnSerial(Type $type): ?Expression
    {
        // Nothing to do, serial is already a sequence in pgsql.
        return null;
    }

    #[\Override]
    protected function writeIndexRename(IndexRename $change): iterable|Expression
    {
        return $this->raw('ALTER INDEX ?::id RENAME TO ?::id', [$change->getName(), $change->getNewName()]);
    }
}
