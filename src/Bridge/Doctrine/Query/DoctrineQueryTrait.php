<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query;

use Doctrine\DBAL\Result;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;

trait DoctrineQueryTrait
{
    private ?DoctrineQueryBuilder $queryBuilder = null;

    /**
     * @internal
     *   Set doctrine/dbal connection.
     */
    public function initialize(DoctrineQueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(): Result
    {
        $query = $this
            ->queryBuilder
            ->getWriter()
            ->prepare($this)
        ;

        return $this
            ->queryBuilder
            ->getConnection()
            ->executeQuery(
                $query->toString(),
                $query->getArguments()->getAll(),
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement(): int
    {
        $query = $this
            ->queryBuilder
            ->getWriter()
            ->prepare($this)
        ;

        return (int) $this
            ->queryBuilder
            ->getConnection()
            ->executeStatement(
                $query->toString(),
                $query->getArguments()->getAll(),
            )
        ;
    }
}
