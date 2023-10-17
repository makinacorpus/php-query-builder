<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Merge as QueryBuilderMerge;

class PdoMerge extends QueryBuilderMerge
{
    use PdoQueryTrait;
}
