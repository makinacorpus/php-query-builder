<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use MakinaCorpus\QueryBuilder\Query\Delete;

class DoctrineDelete extends Delete
{
    use DoctrineQueryTrait;
}
