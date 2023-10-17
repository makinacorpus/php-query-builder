<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Insert as QueryBuilderInsert;

class DoctrineInsert extends  QueryBuilderInsert
{
    use DoctrineQueryTrait;
}
