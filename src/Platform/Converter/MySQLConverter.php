<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Converter;

use MakinaCorpus\QueryBuilder\Converter\Converter;

class MySQLConverter extends Converter
{
    #[\Override]
    protected function toSqlDefault(string $type, mixed $value): null|int|float|string|object
    {
        return match ($type) {
            'bool' => $value ? 1 : 0,
            'boolean' => $value ? 1 : 0,
            default => parent::toSqlDefault($type, $value),
        };
    }
}
