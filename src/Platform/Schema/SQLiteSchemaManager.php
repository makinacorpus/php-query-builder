<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Read\Column;
use MakinaCorpus\QueryBuilder\Schema\Read\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\Read\Index;
use MakinaCorpus\QueryBuilder\Schema\Read\Key;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Please note that some functions here might use information_schema tables
 * which are restricted in listings, they will show up only table information
 * the current user owns or has non-SELECT privileges onto.
 */
class SQLiteSchemaManager extends SchemaManager
{
    #[\Override]
    public function supportsTransaction(): bool
    {
        return true;
    }

    #[\Override]
    public function supportsSchema(): bool
    {
        return false;
    }

    #[\Override]
    public function listDatabases(): array
    {
        // @todo Something better?
        return ['main'];
    }

    #[\Override]
    public function listSchemas(?string $database = null): array
    {
        // @see https://www.sqlite.org/lang_attach.html
        //   We do not support attached databases yet.
        return ['public'];
    }

    #[\Override]
    protected function doListTables(string $database, string $schema): array
    {
        if ('public' !== $schema) {
            return [];
        }

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    name
                FROM sqlite_master
                WHERE
                    type = 'table'
                    AND name != 'sqlite_sequence'
                    AND name != 'geometry_columns'
                    AND name != 'spatial_ref_sys'
                UNION ALL
                SELECT
                    name
                FROM sqlite_temp_master
                WHERE
                    type = 'table'
                ORDER BY name
                SQL,
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    protected function doTableExists(string $database, string $schema, string $name): bool
    {
        return \in_array($name, $this->listTables($database, $schema));
    }

    #[\Override]
    protected function getTableComment(string $database, string $schema, string $name): ?string
    {
        if ('public' !== $schema) {
            return null;
        }

        // @todo Not implemented yet.
        return null;
    }

    #[\Override]
    protected function getTableColumns(string $database, string $schema, string $name): array
    {
        if ('public' !== $schema) {
            return [];
        }

        /*
         * @todo
         *
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
        $defaultCollation = 'binary';

        return $this
            ->session
            ->executeQuery(
                <<<SQL
                PRAGMA table_info(?::table)
                SQL,
                [$name]
            )
            ->setHydrator(function (ResultRow $row) use ($database, $schema, $name, $defaultCollation) {
                $valueType = \strtolower($row->get('type', 'string'));

                $length = $precision = $scale = null;

                $matches = [];
                if (\preg_match('/^(native character|nvarchar|nchar|character|varying character|varchar)\((\d+)\)$/', $valueType, $matches)) {
                    $valueType = $matches[1];
                    $length = (int) $matches[2];
                } else if (\preg_match('/^(decimal)\((\d+),(\d+)\)$/', $valueType, $matches)) {
                    $valueType = $matches[1];
                    $precision = (int) $matches[2];
                    $scale = (int) $matches[3];
                }

                return new Column(
                    collation: $defaultCollation, // @todo
                    comment: null, // @todo,
                    database: $database,
                    name: $row->get('name', 'string'),
                    nullabe: !$row->get('notnull', 'bool'),
                    options: [],
                    schema: $schema,
                    table: $name,
                    // @todo Build Type directly from SQL create string.
                    valueType: new Type(
                        array: false, // @todo
                        internal: InternalType::fromTypeName($valueType),
                        length: $length,
                        name: $valueType,
                        precision: $precision,
                        scale: $scale,
                        unsigned: false,
                        withTimeZone: false,
                    ),
                );
            })
            ->fetchAllHydrated()
        ;
    }

    #[\Override]
    protected function getTablePrimaryKey(string $database, string $schema, string $name): ?Key
    {
        if ('public' !== $schema) {
            return null;
        }

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                PRAGMA index_list(?::table);
                SQL,
                [$name]
            )
        ;

        $keyName = null;
        while ($row = $result->fetchRow()) {
            if ('pk' === $row->get('origin', 'string')) {
                $keyName = $row->get('name', 'string');
            }
        }

        if (!$keyName) {
            return null;
        }

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                PRAGMA table_info(?::table);
                SQL,
                [$name]
            )
        ;

        $columns = [];
        while ($row = $result->fetchRow()) {
            if ($row->get('pk', 'bool')) {
                $columns[] = $row->get('name', 'string');
            }
        }

        return new Key(
            columnNames: $columns,
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
        if ('public' !== $schema) {
            return [];
        }

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                PRAGMA foreign_key_list(?::table);
                SQL,
                [$name]
            )
        ;

        $map = [];
        while ($row = $result->fetchRow()) {
            $map[$row->get('table', 'string')][$row->get('from', 'string')] = $row->get('to', 'string');
        }

        $ret = [];
        if ($map) {
            foreach ($map as $foreignTable => $columnMap) {
                $ret[] = new ForeignKey(
                    columnNames: \array_keys($columnMap),
                    comment: null, // @todo
                    database: $database,
                    foreignColumnNames: \array_values($columnMap),
                    foreignSchema: $schema,
                    foreignTable: $foreignTable,
                    name: $name . '_' . $foreignTable . '_' . \implode('_', $columnMap), // @todo
                    options: [],
                    schema: $schema,
                    table: $name,
                );
            }
        }

        return $ret;
    }

    #[\Override]
    protected function getTableReverseForeignKeys(string $database, string $schema, string $name): array
    {
        if ('public' !== $schema) {
            return [];
        }

        return []; // @todo
    }

    #[\Override]
    protected function getTableIndexes(string $database, string $schema, string $name): array
    {
        $ret = [];

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    index_name,
                    group_concat(column_name, ' ') AS column_names
                FROM (
                    SELECT DISTINCT
                        il.name AS index_name,
                        ii.name AS column_name
                    FROM
                        sqlite_schema AS m,
                        pragma_index_list(m.name) AS il,
                        pragma_index_info(il.name) AS ii
                    WHERE
                        m.type = 'table'
                        AND m.name = ?
                    ORDER BY il.name, ii.seqno
                ) AS a
                GROUP BY index_name
                SQL,
                [$name]
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
    protected function doWriteColumnIdentity(Type $type): ?Expression
    {
        throw new UnsupportedFeatureError("This requires to rewrite the CREATE TABLE statement as well.");
    }

    #[\Override]
    protected function doWriteColumnSerial(Type $type): ?Expression
    {
        throw new UnsupportedFeatureError("This requires to rewrite the CREATE TABLE statement as well.");
    }

    #[\Override]
    protected function writeColumnModify(ColumnModify $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new column, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeConstraintDrop(ConstraintDrop $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeConstraintModify(ConstraintModify $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeConstraintRename(ConstraintRename $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeForeignKeyAdd(ForeignKeyAdd $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeForeignKeyModify(ForeignKeyModify $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeForeignKeyDrop(ForeignKeyDrop $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeForeignKeyRename(ForeignKeyRename $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writeIndexCreate(IndexCreate $change): iterable|Expression
    {
        return $this->raw('CREATE INDEX ?::id ON ? (?::id[])', [$change->getName() ?? $change->generateName(), $this->table($change), $change->getColumns()]);
    }

    #[\Override]
    protected function writeIndexRename(IndexRename $change): iterable|Expression
    {
        return $this->raw('ALTER INDEX ?::id RENAME TO ?::id', [$change->getName(), $change->getNewName()]);
    }

    /**
     * SQLite requires the identity columns to be declared using the exact
     * "colname INTEGER PRIMARY KEY AUTOINCREMENT" statement, otherwise it
     * will not work as expected. We need to check if user defined primary
     * key is the same column, then alter the CREATE TABLE statement for
     * this column and drop the PRIMARY KEY statement at the end.
     */
    #[\Override]
    protected function writeTableCreate(TableCreate $change): iterable|Expression
    {
        $pieces = [];

        $identifierColumn = null;
        foreach ($change->getColumns() as $column) {
            \assert($column instanceof ColumnAdd);

            if ($column->getType()->isIdentity() || $column->getType()->isSerial()) {
                if ($identifierColumn) {
                    throw new UnsupportedFeatureError(\sprintf(
                        "Table '%s' column '%s': SQLite can have only one IDENTITY column per table, '%s' already defined",
                        $change->getName(), $column->getName(), $identifierColumn,
                    ));
                }

                if ($column->isNullable() || null !== $column->getDefault()) {
                    throw new UnsupportedFeatureError(\sprintf(
                        "Table '%s' column '%s': nullable or with default IDENTITY columns is unsupported with SQLite",
                        $change->getName(), $column->getName(),
                    ));
                }

                $identifierColumn = $column->getName();
                $pieces[] = $this->raw('?::column INTEGER PRIMARY KEY AUTOINCREMENT', [$identifierColumn]);
            } else {
                $pieces[] = $this->doWriteColumn($column);
            }
        }

        if ($primaryKey = $change->getPrimaryKey()) {
            if ($identifierColumn) {
                if ([$identifierColumn] !== $primaryKey->getColumns()) {
                    throw new UnsupportedFeatureError(\sprintf(
                        "Table '%s': PRIMARY KEY differs from the given IDENTIY column '%s' and is unsupported with SQLite",
                        $change->getName(), $identifierColumn,
                    ));
                }
                // Drop the PRIMARY KEY statement since it's already written
                // in the column declaration.
            } else {
                $pieces[] = $this->doWritePrimaryKey($primaryKey);
            }
        }

        // The rest is the same code as parent.
        foreach ($change->getUniqueKeys() as $uniqueKey) {
            $pieces[] = $this->doWriteTableCreateUniqueKey($uniqueKey);
        }
        foreach ($change->getForeignKeys() as $foreignKey) {
            $pieces[] = $this->doWriteForeignKey($foreignKey);
        }

        $placeholder = \implode(', ', \array_fill(0, \count($pieces), '?'));

        if ($change->isTemporary()) {
            yield $this->raw('CREATE TEMPORARY TABLE ? (' . $placeholder . ')', [$this->table($change), ...$pieces]);
        } else {
            yield $this->raw('CREATE TABLE ? (' . $placeholder . ')', [$this->table($change), ...$pieces]);
        }

        foreach ($change->getIndexes() as $index) {
            yield $this->writeIndexCreate($index);
        }
    }

    #[\Override]
    protected function writeUniqueKeyAdd(UniqueKeyAdd $change): iterable|Expression
    {
        if (!$change->isNullsDistinct()) {
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        return $this->raw('CREATE UNIQUE INDEX ?::id ON ? (?::id[])', [$change->getName() ?? $change->generateName(), $this->table($change), $change->getColumns()]);
    }

    #[\Override]
    protected function writePrimaryKeyAdd(PrimaryKeyAdd $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }

    #[\Override]
    protected function writePrimaryKeyDrop(PrimaryKeyDrop $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("This operation requires creating a new table, copying data, then renaming it.");
    }
}
