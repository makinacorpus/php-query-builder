<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Insert;

class DoctrineInsert extends Insert
{
    use DoctrineQueryTrait;
}
