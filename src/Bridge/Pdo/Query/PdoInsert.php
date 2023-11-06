<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Insert;

class PdoInsert extends Insert
{
    use PdoQueryTrait;
}
