<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Update as QueryBuilderUpdate;

class DoctrineUpdate extends QueryBuilderUpdate
{
    use DoctrineQueryTrait;
}
