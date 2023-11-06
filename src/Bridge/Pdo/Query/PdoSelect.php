<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Query\Select;

class PdoSelect extends Select
{
    use PdoQueryTrait;
}
