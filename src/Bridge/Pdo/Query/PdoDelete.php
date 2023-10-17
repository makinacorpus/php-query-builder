<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Delete as QueryBuilderDelete;

class PdoDelete extends QueryBuilderDelete
{
    use PdoQueryTrait;
}
