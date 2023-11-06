<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Update;

class PdoUpdate extends Update
{
    use PdoQueryTrait;
}
