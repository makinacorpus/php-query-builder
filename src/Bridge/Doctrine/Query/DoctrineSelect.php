<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Select as QueryBuilderSelect;

class DoctrineSelect extends QueryBuilderSelect
{
    use DoctrineQueryTrait;
}
