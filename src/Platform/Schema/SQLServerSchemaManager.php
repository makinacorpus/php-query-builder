<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Read\Column;
use MakinaCorpus\QueryBuilder\Schema\Read\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\Read\Index;
use MakinaCorpus\QueryBuilder\Schema\Read\Key;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Warning: when using SQL Server, most "sys".* tables will restrict results
 * with objects from the current database only. This means that, in most if
 * not all cases, result will always ignore the $database parameter and use
 * the current database instead.
 *
 * Warning: most of this code will not work when there is dot (".") in schema
 * or table names.
 */
class SQLServerSchemaManager extends SchemaManager
{
    #[\Override]
    public function supportsTransaction(): bool
    {
        return false;
    }

    #[\Override]
    public function listDatabases(): array
    {
        // DB_NAME() gives the current database.

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT databases.name FROM sys.databases
                SQL
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    public function listSchemas(?string $database = null): array
    {
        // SCHEMA_NAME() gives current schema.

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    schemata.schema_name
                FROM information_schema.schemata
                WHERE
                    schemata.catalog_name = ?
                    AND schemata.schema_name != 'sys'
                    AND schemata.schema_name != 'INFORMATION_SCHEMA'
                    AND schemata.schema_name != 'guest'
                ORDER BY schemata.schema_name ASC
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
                    objects.name
                FROM sys.objects
                WHERE
                    objects.type = 'U'
                    AND objects.schema_id = SCHEMA_ID(?)
                    AND objects.is_ms_shipped = 0
                ORDER BY name ASC
                SQL,
                [$schema]
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
                SELECT 1
                FROM sys.objects
                WHERE
                    type = 'U'
                    AND schema_id = SCHEMA_ID(?)
                    AND name = ?
                    AND is_ms_shipped = 0
                SQL,
                [$schema, $name]
            )
            ->fetchOne()
        ;
    }

    #[\Override]
    protected function getTableComment(string $database, string $schema, string $name): ?string
    {
        return null; // @todo
    }

    #[\Override]
    protected function getTableColumns(string $database, string $schema, string $name): array
    {
        /*
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
         */

        $defaultCollation = null;

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    col.object_id,
                    col.name,
                    type.name AS type,
                    col.max_length,
                    col.is_nullable,
                    def.definition AS [default],
                    col.scale,
                    col.precision,
                    col.is_identity AS autoincrement,
                    col.collation_name AS collation,
                    CAST(prop.value AS NVARCHAR(MAX)) AS comment
                FROM sys.columns AS col
                JOIN sys.types AS type
                    ON col.user_type_id = type.user_type_id
                JOIN sys.objects AS obj
                    ON col.object_id = obj.object_id
                JOIN sys.schemas AS scm
                    ON obj.schema_id = scm.schema_id
                LEFT JOIN sys.default_constraints def
                    ON col.default_object_id = def.object_id
                    AND col.object_id = def.parent_object_id
                LEFT JOIN sys.extended_properties AS prop
                    ON obj.object_id = prop.major_id
                    AND col.column_id = prop.minor_id
                    AND prop.name = 'MS_Description'
                WHERE 
                    obj.type = 'U'
                    AND SCHEMA_NAME(obj.schema_id) = ?
                    AND OBJECT_NAME(col.object_id) = ?
                ORDER BY col.column_id
                SQL,
                [$schema, $name]
            )
            ->setHydrator(fn (ResultRow $row) => new Column(
                collation: $row->get('collation', 'string') ?? $defaultCollation,
                comment: $row->get('comment', 'string'),
                database: $database,
                name: $row->get('name', 'string'),
                nullabe: $row->get('is_nullable', 'bool'),
                options: [],
                schema: $schema,
                table: $name,
                // @todo Build Type directly from SQL create string.
                valueType: $this->getTableColumnsType(
                    $row->get('type', 'string'),
                    new Type(
                        array: false, // @todo
                        internal: InternalType::fromTypeName($row->get('type', 'string')),
                        length: $row->get('max_length', 'int'),
                        name: $row->get('type', 'string'),
                        precision: $row->get('precision', 'int'),
                        scale: $row->get('scale', 'int'),
                        unsigned: false,
                        withTimeZone: false, // @todo
                    )),
            ))
            ->fetchAllHydrated()
        ;
    }

    /**
     * Do some magic in type interpretation.
     */
    private function getTableColumnsType(string $typeName, Type $type): Type
    {
        // https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
        // Integer max length:
        //   - 1 => Tiny
        //   - 2 => Small,
        //   - 4 => Normal
        //   - 8 => Big
        if ($type->length && InternalType::INT === $type->internal) {
            if ($type->length <= 1) {
                return new Type(
                    array: $type->array,
                    internal: InternalType::INT_TINY,
                    name: $type->name,
                );
            } else if ($type->length <= 2) {
                return new Type(
                    array: $type->array,
                    internal: InternalType::INT_SMALL,
                    name: $type->name,
                );
            } else if ($type->length <= 4) {
                return new Type(
                    array: $type->array,
                    internal: InternalType::INT,
                    name: $type->name,
                );
            } else {
                return new Type(
                    array: $type->array,
                    internal: InternalType::INT_BIG,
                    name: $type->name,
                );
            }
        }

        // "-1" means nvarchar(max).
        if ($type->length < 0 && InternalType::VARCHAR === $type->internal) {
            return new Type(
                array: $type->array,
                internal: InternalType::TEXT,
                name: $type->name,
            );
        }

        // "n" prefix means "national", which means multibyte.
        // SQL Server multibyte is UTF8, then 2 bytes each char.
        if ($type->length && \in_array($typeName, ['nvarchar', 'ntext'])) {
            return new Type(
                array: $type->array,
                internal: InternalType::VARCHAR,
                name: $type->name,
                length: (int) \floor($type->length / 2),
            );
        }

        return $type;
    }

    #[\Override]
    protected function getTablePrimaryKey(string $database, string $schema, string $name): ?Key
    {
        $objectIdStr = $schema . '.' . $name;

        $row = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    indexes.name,
                    indexes.object_id,
                    indexes.index_id
                FROM sys.indexes
                WHERE
                    indexes.is_primary_key = 1
                    AND indexes.object_id = OBJECT_ID(?)
                SQL,
                [$objectIdStr]
            )
            ->fetchRow()
        ;

        if (!$row) {
            return null;
        }

        $keyName = $row->get('name', 'string');

        $columnNames = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    columns.name
                FROM sys.index_columns
                INNER JOIN sys.columns
                    ON columns.column_id = index_columns.column_id
                    AND columns.object_id = index_columns.object_id
                WHERE
                    index_columns.object_id = ?
                    AND index_columns.index_id = ?
                ORDER BY index_columns.key_ordinal
                SQL,
                [
                    $row->get('object_id', 'int'),
                    $row->get('index_id', 'int'),
                ]
            )
            ->fetchFirstColumn()
        ;

        return new Key(
            columnNames: $columnNames,
            comment: null,
            database: $database,
            name: $keyName,
            options: [],
            schema: $schema,
            table: $name,
        );
    }

    #[\Override]
    protected function getTableForeignKeys(string $database, string $schema, string $name): array
    {
        return \array_values(\array_filter(
            $this->getAllTableKeysInfo($database, $schema, $name),
            fn (ForeignKey $key) => ($key->getTable() === $name && $key->getSchema() === $schema),
        ));
    }

    #[\Override]
    protected function getTableReverseForeignKeys(string $database, string $schema, string $name): array
    {
        return \array_values(\array_filter(
            $this->getAllTableKeysInfo($database, $schema, $name),
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
     */
    private function getAllTableKeysInfo(string $database, string $schema, string $name): array
    {
        $ret = [];

        $objectIdStr = $schema . '.' . $name;

        $foreignKeyInfo = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    foreign_keys.object_id,
                    foreign_keys.name,
                    source.name AS source_table,
                    SCHEMA_NAME(source.schema_id) AS source_schema,
                    referenced.name AS referenced_table,
                    SCHEMA_NAME(referenced.schema_id) AS referenced_schema,
                    foreign_keys.delete_referential_action,
                    foreign_keys.update_referential_action
                FROM sys.foreign_keys
                INNER JOIN sys.objects AS source
                    ON source.object_id = foreign_keys.parent_object_id
                INNER JOIN sys.objects AS referenced
                    ON referenced.object_id = foreign_keys.referenced_object_id
                WHERE
                    foreign_keys.type = 'F'
                    AND (
                        foreign_keys.parent_object_id = OBJECT_ID(?)
                        OR foreign_keys.referenced_object_id = OBJECT_ID(?)
                    )
                SQL,
                [$objectIdStr, $objectIdStr]
            )
        ;

        while ($row = $foreignKeyInfo->fetchRow()) {
            $keyColumns = $this->session->executeQuery(
                <<<SQL
                SELECT
                    COL_NAME(
                        foreign_key_columns.parent_object_id,
                        foreign_key_columns.parent_column_id
                    ) AS source_column,
                    COL_NAME(
                        foreign_key_columns.referenced_object_id,
                        foreign_key_columns.referenced_column_id
                    ) AS referenced_column
                FROM sys.foreign_key_columns
                WHERE
                    foreign_key_columns.constraint_object_id = ?
                SQL,
                [$row->get('object_id', 'int')]
            )->fetchAllKeyValue();

            $ret[] = new ForeignKey(
                columnNames: \array_keys($keyColumns),
                comment: null, // @todo
                database: $database,
                foreignColumnNames: \array_values($keyColumns),
                foreignSchema: $row->get('referenced_schema', 'string'),
                foreignTable: $row->get('referenced_table', 'string'),
                name: $row->get('name', 'string'),
                options: [],
                schema: $row->get('source_schema', 'string'),
                table: $row->get('source_table', 'string'),
            );
        }

        return $ret;
    }

    /**
     * Find column default value constraint name.
     */
    private function findColumnConstraintDefaultName(string $schema, string $table, string $column): ?string
    {
        return $this->session->executeQuery(
            <<<SQL
            SELECT
                default_constraints.name
            FROM sys.columns
            INNER JOIN sys.default_constraints
                ON columns.column_id = default_constraints.parent_column_id
                AND columns.object_id = default_constraints.parent_object_id
            WHERE
                columns.object_id = OBJECT_ID(?)
                AND columns.name = ?
            SQL,
            [$schema . '.' . $table, $column]
        )->fetchOne();
    }

    /*
    private function findColumnUniqueKeyConstraintName(string $schema, string $table, string $column): ?string
    {
        return $this->session->executeQuery(
            <<<SQL
            SELECT
                indexes.name
            FROM sys.indexes
            INNER JOIN sys.index_columns
                ON index_columns.object_id = indexes.object_id
                AND index_columns.index_id = indexes.index_id
            INNER JOIN sys.columns
                ON columns.object_id = index_columns.object_id
                AND columns.column_id = index_columns.column_id
            WHERE
                indexes.object_id = OBJECT_ID(?)
                AND columns.name = ?
            SQL,
            [$schema . '.' . $table, $column]
        )->fetchOne();
    }
     */

    #[\Override]
    protected function getTableIndexes(string $database, string $schema, string $name): array
    {
        $ret = [];

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    a.index_name,
                    string_agg(a.column_name, ' ') AS column_names
                FROM (
                    SELECT
                        i.name AS index_name,
                        c.name AS column_name
                    FROM sys.indexes i
                    INNER JOIN sys.index_columns ic
                        ON i.index_id = ic.index_id
                        AND i.object_id = ic.object_id
                    INNER JOIN sys.columns c
                        ON c.column_id = ic.column_id
                        AND c.object_id = i.object_id
                    WHERE
                        i.object_id = OBJECT_ID(?)
                    ORDER BY i.name, ic.key_ordinal OFFSET 0 ROWS
                ) AS a
                GROUP BY a.index_name
                SQL,
                [$schema . '.' . $name]
            )
        ;

        while ($row = $result->fetchRow()) {
            $ret[] = new Index(
                columnNames: \explode(' ', $row->get('column_names', 'string')),
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
    protected function doWriteColumnCollation(string $collation): Expression
    {
        return $this->raw('COLLATE ' . $collation);
    }

    #[\Override]
    protected function doWriteColumnIdentity(Type $type): ?Expression
    {
        return $this->raw('IDENTITY(1, 1)');
    }

    #[\Override]
    protected function doWriteColumnSerial(Type $type): ?Expression
    {
        return $this->raw('IDENTITY(1, 1)');
    }

    #[\Override]
    protected function writeColumnAdd(ColumnAdd $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? ADD ?', [$this->table($change), $this->doWriteColumn($change)]);
    }

    #[\Override]
    protected function writeColumnModify(ColumnModify $change): iterable|Expression
    {
        $statements = [];

        $name = $change->getName();
        $tableExpr = $this->table($change);

        $fullChange = $this->columnModifyToAdd($change);
        $type = $fullChange->getType();

        // It's mandatory to remove default prior doing anything else.
        // If type change, it will raise an error.
        // You can only add a default, not change it, it will raise an error.
        // In all case, we remove it, then set it back once column is altered.
        if ($defaultConstraintName = $this->findColumnConstraintDefaultName($change->getSchema(), $change->getTable(), $change->getName())) {
            \array_unshift($statements, $this->raw('ALTER TABLE ? DROP CONSTRAINT ?::id', [$tableExpr, $defaultConstraintName]));
        }

        // @todo remove nullable constraints

        $parts = [];
        $parts[] = $this->doWriteColumnType($fullChange);
        if ($fullChange->isNullable()) {
            $parts[] = $this->raw('NULL');
        } else {
            $parts[] = $this->raw('NOT NULL');
        }
        $statements[] = $this->raw('ALTER TABLE ? ALTER COLUMN ?::id ' . \implode(' ', \array_fill(0, \count($parts), '?')), [$tableExpr, $name, ...$parts]);

        if ($change->isDropDefault()) {
            if ($change->getDefault()) {
                throw new QueryBuilderError(\sprintf("Column '%s': you cannot drop default and set default at the same time.", $name));
            }
        } else if ($default = $fullChange->getDefault()) {
            $statements[] = $this->raw('ALTER TABLE ? ADD DEFAULT ? FOR ?::id', [$tableExpr, $this->doWriteColumnDefault($type, $default), $name]);
        }

        return $statements;
    }

    #[\Override]
    protected function writeColumnRename(ColumnRename $change): iterable|Expression
    {
        $previous = \implode('.', \array_filter([$change->getSchema(), $change->getTable(), $change->getName()]));
        $new = $change->getNewName();

        // https://learn.microsoft.com/en-us/sql/relational-databases/tables/rename-columns-database-engine?view=sql-server-ver16
        // @todo We do not handle cascade renaming.
        return $this->raw('EXEC sp_rename ?, ?, ?', [$previous, $new, 'COLUMN']);
    }

    #[\Override]
    protected function doWriteForeignKey(ForeignKeyAdd $change): Expression
    {
        if ($change->isDeferrable()) {
            // @todo log that SQL Server doesn't support deferring?
        }

        return $this->doWriteConstraint(
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
        // SQL Server requires a name.
        $name = $change->getName() ?? $change->generateName();

        return $this->raw('CREATE INDEX ?::id ON ? (?::id[])', [$name, $this->table($change), $change->getColumns()]);
    }

    #[\Override]
    protected function writeIndexDrop(IndexDrop $change): iterable|Expression
    {
        return $this->raw('DROP INDEX ?::id ON ?', [$change->getName(), $this->table($change->getTable(), $change->getSchema())]);
    }

    #[\Override]
    protected function writeIndexRename(IndexRename $change): iterable|Expression
    {
        return $this->raw('ALTER INDEX ?::id RENAME TO ?::id', [$change->getName(), $change->getNewName()]);
    }

    #[\Override]
    protected function writeTableRename(TableRename $change): iterable|Expression
    {
        $previous = \implode('.', \array_filter([$change->getSchema(), $change->getName()]));
        $new = $change->getNewName();

        // https://learn.microsoft.com/en-us/sql/relational-databases/tables/rename-tables-database-engine?view=sql-server-ver16
        // @todo We do not handle cascade renaming.
        return $this->raw('EXEC sp_rename ?, ?, ?', [$previous, $new]);
    }

    #[\Override]
    protected function writeUniqueKeyAdd(UniqueKeyAdd $change): iterable|Expression
    {
        if (!$change->isNullsDistinct()) {
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        // SQL Server requires a name.
        $name = $change->getName() ?? $change->generateName();

        return $this->raw('CREATE UNIQUE INDEX ?::id ON ? (?::id[])', [$name, $this->table($change), $change->getColumns()]);
    }

    #[\Override]
    protected function writeUniqueKeyDrop(UniqueKeyDrop $change): iterable|Expression
    {
        return $this->raw('DROP INDEX ?::id ON ?', [$change->getName(), $this->table($change)]);
    }
}
