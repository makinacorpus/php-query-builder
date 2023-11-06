<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\RawQuery;

class PdoRawQuery extends RawQuery
{
    use PdoQueryTrait;
}
