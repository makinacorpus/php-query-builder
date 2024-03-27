<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Type;

use MakinaCorpus\QueryBuilder\Type\TypeConverter;

class PostgreSQLTypeConverter extends TypeConverter
{
    #[\Override]
    public function getTinyIntType(): string
    {
        return 'smallint';
    }

    #[\Override]
    public function getSmallFloatType(): string
    {
        return 'float4';
    }

    #[\Override]
    public function getFloatType(): string
    {
        return 'float';
    }

    #[\Override]
    public function getBigFloatType(): string
    {
        return 'float8';
    }

    #[\Override]
    public function getSmallSerialType(): string
    {
        return 'serial4';
    }

    #[\Override]
    public function getSerialType(): string
    {
        return 'serial';
    }

    #[\Override]
    public function getBigSerialType(): string
    {
        return 'serial8';
    }

    #[\Override]
    public function getJsonType(): string
    {
        return 'jsonb';
    }

    #[\Override]
    public function getBinaryType(): string
    {
        return 'bytea';
    }
}
