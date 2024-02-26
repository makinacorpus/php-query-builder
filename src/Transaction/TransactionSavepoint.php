<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Transaction;

use MakinaCorpus\QueryBuilder\Error\Bridge\TransactionError;

final class TransactionSavepoint implements Transaction
{
    public function __construct(
        private string $name,
        private Transaction $root,
        private bool $running = true,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function level(int $isolationLevel): static
    {
        @\trigger_error(\sprintf("Cannot change transaction level in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->running && $this->root->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function start(): static
    {
        if (!$this->running) {
            throw new TransactionError(\sprintf("Cannot restart a rollbacked transaction with savedpoint '%s'", $this->name));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function immediate($constraint = null): static
    {
        if ($constraint) {
            $this->root->immediate($constraint);
        } else {
            @\trigger_error(\sprintf("Cannot set all constraints to immediate in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deferred($constraint = null): static
    {
        if ($constraint) {
            $this->root->deferred($constraint);
        } else {
            @\trigger_error(\sprintf("Cannot set all constraints to deferred in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function savepoint(?string $name = null): TransactionSavepoint
    {
        if ($name) {
            return $this->root->savepoint($name);
        }
        return $this->root->savepoint();
    }

    /**
     * {@inheritdoc}
     */
    public function isNested(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->root->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getSavepointName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): static
    {
        $this->running = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): static
    {
        $this->running = false;
        $this->root->rollbackToSavepoint($this->name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackToSavepoint(string $name): static
    {
        $this->root->rollbackToSavepoint($name);

        return $this;
    }
}
