<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Converter;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

class MySQLConverter extends Converter
{
    #[\Override]
    protected function toSqlDefault(Type $type, mixed $value): null|int|float|string|object
    {
        return match ($type->internal) {
            InternalType::BOOL => $value ? 1 : 0,
            default => parent::toSqlDefault($type, $value),
        };
    }
}
