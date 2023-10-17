<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Update as QueryBuilderUpdate;

class PdoUpdate extends QueryBuilderUpdate
{
    use PdoQueryTrait;
}
