<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Type;

use MakinaCorpus\QueryBuilder\Type\TypeConverter;

class MySQLTypeConverter extends TypeConverter
{
    #[\Override]
    public function getBoolType(): string
    {
        return 'tinyint';
    }

    #[\Override]
    public function getSmallFloatType(): string
    {
        return 'float';
    }

    #[\Override]
    public function getTinyIntType(): string
    {
        return 'tinyint';
    }

    #[\Override]
    public function getFloatType(): string
    {
        return 'float';
    }

    #[\Override]
    public function getBigFloatType(): string
    {
        return 'float';
    }

    #[\Override]
    public function getJsonType(): string
    {
        return 'text';
    }

    #[\Override]
    public function getTimestampType(): string
    {
        return 'datetime';
    }

    #[\Override]
    public function getIntCastType(): string
    {
        return 'integer';
    }

    #[\Override]
    public function getUuidType(): string
    {
        return 'varchar(36)';
    }
}
