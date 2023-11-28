<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\OptionsBag;
use MakinaCorpus\QueryBuilder\QueryExecutor;
use MakinaCorpus\QueryBuilder\Query\Partial\AliasHolderTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\WithClauseTrait;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

abstract class AbstractQuery implements Query
{
    use AliasHolderTrait;
    use WithClauseTrait;

    private ?QueryExecutor $queryExecutor = null;
    private ?string $identifier = null;
    private ?OptionsBag $options = null;
    private ?ExpressionFactory $expressionFactory = null;

    /**
     * @internal
     *   For bridges only.
     */
    public function setQueryExecutor(QueryExecutor $queryExecutor): void
    {
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return $this->willReturnRows();
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function expression(): ExpressionFactory
    {
        return $this->expressionFactory ??= new ExpressionFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption(string $name, $value): static
    {
        $this->getOptions()->set($name, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): static
    {
        $this->getOptions()->setAll($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): OptionsBag
    {
        return $this->options ??= new OptionsBag();
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(): Result
    {
        if (!$this->queryExecutor) {
            throw new QueryBuilderError("Query executor is not set.");
        }

        return $this->queryExecutor->executeQuery($this);
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement(): int
    {
        if (!$this->queryExecutor) {
            throw new QueryBuilderError("Query executor is not set.");
        }

        return $this->queryExecutor->executeStatement($this);
    }
}
