<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Select;

class DoctrineSelect extends Select
{
    use DoctrineQueryTrait;
}
