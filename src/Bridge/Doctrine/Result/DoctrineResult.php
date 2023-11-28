<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine;

use Doctrine\DBAL\Result;
use MakinaCorpus\QueryBuilder\Result\AbstractResult;

class DoctrineResult extends AbstractResult
{
    public function __construct(Result $result)
    {
        
    }
}
