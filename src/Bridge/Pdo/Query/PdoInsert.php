<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Insert as QueryBuilderInsert;

class PdoInsert extends QueryBuilderInsert
{
    use PdoQueryTrait;
}
