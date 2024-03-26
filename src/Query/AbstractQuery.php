<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\OptionsBag;
use MakinaCorpus\QueryBuilder\Query\Partial\AliasHolderTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\WithClauseTrait;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Type\Type;

abstract class AbstractQuery implements Query
{
    use AliasHolderTrait;
    use WithClauseTrait;

    private ?DatabaseSession $session = null;
    private ?string $identifier = null;
    private ?OptionsBag $options = null;
    private ?ExpressionFactory $expressionFactory = null;

    /**
     * @internal
     *   For bridges only.
     */
    public function setDatabaseSession(DatabaseSession $session): void
    {
        $this->session = $session;
    }

    #[\Override]
    public function returns(): bool
    {
        return $this->willReturnRows();
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    #[\Override]
    public function expression(): ExpressionFactory
    {
        return $this->expressionFactory ??= new ExpressionFactory();
    }

    #[\Override]
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    #[\Override]
    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    #[\Override]
    public function setOption(string $name, $value): static
    {
        $this->getOptions()->set($name, $value);

        return $this;
    }

    #[\Override]
    public function setOptions(array $options): static
    {
        $this->getOptions()->setAll($options);

        return $this;
    }

    #[\Override]
    public function getOptions(): OptionsBag
    {
        return $this->options ??= new OptionsBag();
    }

    #[\Override]
    public function executeQuery(): Result
    {
        if (!$this->session) {
            throw new QueryBuilderError("Database session is not set.");
        }

        return $this->session->executeQuery($this);
    }

    #[\Override]
    public function executeStatement(): int
    {
        if (!$this->session) {
            throw new QueryBuilderError("Database session is not set.");
        }

        return $this->session->executeStatement($this);
    }
}
