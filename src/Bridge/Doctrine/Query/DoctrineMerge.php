<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Merge;

class DoctrineMerge extends Merge
{
    use DoctrineQueryTrait;
}
