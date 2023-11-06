<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\RawQuery;

class DoctrineRawQuery extends RawQuery
{
    use DoctrineQueryTrait;
}
