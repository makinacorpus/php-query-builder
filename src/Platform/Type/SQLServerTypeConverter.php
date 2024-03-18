<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Type;

use MakinaCorpus\QueryBuilder\Type\TypeConverter;

class SQLServerTypeConverter extends TypeConverter
{
    #[\Override]
    public function getBoolType(): string
    {
        return 'smallint';
    }

    #[\Override]
    public function getFloatType(): string
    {
        return 'real';
    }

    #[\Override]
    public function getJsonType(): string
    {
        return 'ntext';
    }

    #[\Override]
    public function getDateIntervalType(): string
    {
        return 'ntext';
    }

    #[\Override]
    public function getTimestampType(): string
    {
        return 'datetime2';
    }

    #[\Override]
    public function getCharType(): string
    {
        return 'nchar';
    }

    #[\Override]
    public function getVarcharType(): string
    {
        return 'nvarchar';
    }

    #[\Override]
    public function getTextType(): string
    {
        return 'ntext';
    }

    #[\Override]
    public function getBinaryType(): string
    {
        return 'binary';
    }
}
