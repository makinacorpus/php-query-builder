<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Select as QueryBuilderSelect;

class PdoSelect extends QueryBuilderSelect
{
    use PdoQueryTrait;
}
