<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Query;

use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;

trait PdoQueryTrait
{
    private ?PdoQueryBuilder $queryBuilder = null;

    /**
     * @internal
     *   Set doctrine/dbal connection.
     */
    public function initialize(PdoQueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Execute query and return PDO statement.
     */
    public function executeQuery(): \PDOStatement
    {
        $query = $this
            ->queryBuilder
            ->getWriter()
            ->prepare($this)
        ;

        $statement = $this
            ->queryBuilder
            ->getConnection()
            ->prepare($query->toString())
        ;

        $statement->execute($query->getArguments()->getAll());

        return $statement;
    }

    /**
     * Execute statement and return affected row count.
     */
    public function executeStatement(): int
    {
        $query = $this
            ->queryBuilder
            ->getWriter()
            ->prepare($this)
        ;

        $statement = $this
            ->queryBuilder
            ->getConnection()
            ->prepare($query->toString())
        ;

        $statement->execute($query->getArguments()->getAll());

        return $statement->rowCount();
    }
}
