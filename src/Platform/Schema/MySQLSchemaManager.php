<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Schema;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyDrop;
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
            ->session
            ->executeQuery(
                <<<SQL
                SHOW DATABASES
                SQL
            )
            ->fetchFirstColumn()
        ;
    }

    #[\Override]
    public function listSchemas(?string $database = null): array
    {
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
    protected function doTableExists(string $database, string $schema, string $name): bool
    {
        if ('public' !== $schema) {
            return false;
        }

        return (bool) $this
            ->session
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
    protected function getTableComment(string $database, string $schema, string $name): ?string
    {
        return $this
            ->session
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
        $defaultCollation = null; // @todo

        // @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-columns-table.html
        return $this
            ->session
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

                $typeName = \strtolower($row->get('data_type', 'string'));
                $precision = $row->get('numeric_precision', 'int');

                // Integer precision:
                //   - 3 => Tiny
                //   - 5 => Small,
                //   - ? => Medium,
                //   - 10 => Normal
                //   - 19 => Big
                if ($precision && 'int' === $typeName) {
                    if ($precision <= 3) {
                        $typeName = 'tinyint';
                    } else if ($precision <= 5) {
                        $typeName = 'smallint';
                    } else if ($precision <= 10) {
                        $typeName = 'int';
                    } else {
                        $typeName = 'bigint';
                    }
                }

                return new Column(
                    collation: $row->get('collation_name', 'string') ?? $defaultCollation,
                    comment: $row->get('column_comment', 'string'),
                    database: $database,
                    name: $row->get('column_name', 'string'),
                    nullabe: $row->get('is_nullable', 'string') !== 'NO',
                    options: [],
                    schema: 'public',
                    table: $name,
                    // @todo Build Type directly from SQL create string.
                    valueType: new Type(
                        array: false, // @todo
                        internal: InternalType::fromTypeName($typeName),
                        length: $row->get('character_maximum_length', 'int'),
                        name: $typeName,
                        precision: $precision,
                        scale: $row->get('numeric_scale', 'int'),
                        unsigned: \str_contains($creationType, 'unsigned'),
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
        $primaryKeyColumns = $this
            ->session
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
     *
     * @return ForeignKey[]
     */
    private function getAllTableKeysInfo(string $database, string $schema, string $name): array
    {
        $ret = [];

        $foreignKeyInfo = $this
            ->session
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

            $keyColumns = $this->session->executeQuery(
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
    protected function getTableIndexes(string $database, string $schema, string $name): array
    {
        $ret = [];

        $result = $this
            ->session
            ->executeQuery(
                <<<SQL
                SELECT
                    INDEX_NAME AS index_name,
                    GROUP_CONCAT(COLUMN_NAME SEPARATOR ' ') AS column_names
                FROM (
                    SELECT
                        INDEX_NAME,
                        COLUMN_NAME
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE
                        TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                    ORDER BY SEQ_IN_INDEX
                ) AS a
                GROUP BY INDEX_NAME
                SQL,
                [$database, $name]
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
        // This is very hasardous, but let's do it.
        if ('binary' === $collation) {
            $characterSet = 'binary';
        } else if (\str_contains($collation, '_')) {
            list($characterSet,) = \explode('_', $collation, 2);
        } else {
            $characterSet = $collation;
            $collation .= '_general_ci';
        }

        // @todo SQL injection possibility
        return $this->raw('CHARACTER SET ? COLLATE ?', [$characterSet, $collation]);
    }

    #[\Override]
    protected function doWriteColumnIdentity(Type $type): ?Expression
    {
        return $this->raw('auto_increment');
    }

    #[\Override]
    protected function doWriteColumnSerial(Type $type): ?Expression
    {
        return $this->raw('auto_increment');
    }

    #[\Override]
    protected function writeColumnModify(ColumnModify $change): iterable|Expression
    {
        $name = $change->getName();
        $type = $change->getType();

        // When modifying a column in MySQL, you need to use the CHANGE COLUMN
        // syntax if you change anything else than the DEFAULT value.
        // That's kind of very weird, but that's it. Live with it.
        if ($type || null !== $change->isNullable() || $change->getCollation()) {
            return $this->raw(
                'ALTER TABLE ? MODIFY COLUMN ?',
                [
                    $this->table($change),
                    $this->doWriteColumn($this->columnModifyToAdd($change)),
                ]
            );
        }

        $pieces = [];

        if ($change->isDropDefault()) {
            if ($change->getDefault()) {
                // @todo Find a way to have a "identification method" on changes.
                throw new QueryBuilderError(\sprintf("Column '%s': you cannot drop default and set default at the same time.", $name));
            }
            $pieces[] = $this->raw('ALTER COLUMN ?::id DROP DEFAULT', [$name]);
        } else if ($default = $change->getDefault()) {
            // @phpstan-ignore-next-line
            $pieces[] = $this->raw('ALTER COLUMN ?::id SET DEFAULT ?', [$name, $this->doWriteColumnDefault($type ?? Type::raw('unknown'), $default)]);
        }

        return $this->raw('ALTER TABLE ? ' . \implode(', ', \array_fill(0, \count($pieces), '?')), [$this->table($change), ...$pieces]);
    }

    #[\Override]
    protected function doWriteForeignKey(ForeignKeyAdd $change): Expression
    {
        if ($change->isDeferrable()) {
            // @todo log that MySQL doesn't support deferring?
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
    protected function writeUniqueKeyAdd(UniqueKeyAdd $change): Expression
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
    protected function writeUniqueKeyDrop(UniqueKeyDrop $change): Expression
    {
        return $this->doWriteConstraintDrop($change->getName(), $change->getTable(), $change->getSchema());
    }
}
