<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Type;

class MySQL8TypeConverter extends MySQLTypeConverter
{
    #[\Override]
    public function getJsonType(): string
    {
        return 'json';
    }
}
