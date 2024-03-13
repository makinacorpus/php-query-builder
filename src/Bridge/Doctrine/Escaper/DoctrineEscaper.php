<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Escaper;

use Doctrine\DBAL\Connection;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;

class DoctrineEscaper extends StandardEscaper
{
    public function __construct(
        private Connection $connection,
    ) {
        parent::__construct('?', null);
    }

    #[\Override]
    public function escapeLiteral(string $string): string
    {
        return $this->connection->quote($string);
    }
}
