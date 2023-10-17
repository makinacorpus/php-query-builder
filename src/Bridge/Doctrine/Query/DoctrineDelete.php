<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Delete as QueryBuilderDelete;

class DoctrineDelete extends QueryBuilderDelete
{
    use DoctrineQueryTrait;
}
