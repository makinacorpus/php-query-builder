<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Merge as QueryBuilderMerge;

class DoctrineMerge extends QueryBuilderMerge
{
    use DoctrineQueryTrait;
}
