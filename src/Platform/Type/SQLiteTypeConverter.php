<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Type;

use MakinaCorpus\QueryBuilder\Type\TypeConverter;

class SQLiteTypeConverter extends TypeConverter
{
    #[\Override]
    public function getBoolType(): string
    {
        return 'smallint';
    }

    #[\Override]
    public function getJsonType(): string
    {
        return 'text';
    }

    #[\Override]
    public function getDateIntervalType(): string
    {
        return 'text';
    }

    #[\Override]
    public function getDateType(): string
    {
        return 'date';
    }

    #[\Override]
    public function getTimeType(): string
    {
        return 'text';
    }

    /**
     * SQLite doesn't have a "datetime" type, you may store them as text
     * or integer UNIX timestamps. But, it still can cast using this type
     * alias, so let's keep it.
     */
    #[\Override]
    public function getTimestampType(): string
    {
        return 'datetime';
    }

    #[\Override]
    public function getUuidType(): string
    {
        return 'varchar(36)';
    }
}
