<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\OptionsBag;
use MakinaCorpus\QueryBuilder\SqlString;
use MakinaCorpus\QueryBuilder\Driver\Driver;
use MakinaCorpus\QueryBuilder\Driver\DriverAwareTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\AliasHolderTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\WithClauseTrait;

abstract class AbstractQuery implements Query
{
    use AliasHolderTrait;
    use DriverAwareTrait;
    use WithClauseTrait;

    private ?Driver $driver = null;
    private ?string $identifier = null;
    private ?OptionsBag $options = null;

    /**
     * {@inheritdoc}
     */
    public function generate(): SqlString
    {
        return $this->getWriter()->prepare($this);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): mixed
    {
        return $this->getDriver()->execute($this->getWriter()->prepare($this));
    }

    /**
     * {@inheritdoc}
     */
    public function perform(): int
    {
        return $this->getDriver()->perform($this->getWriter()->prepare($this));
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
        return $this->options ?? ($this->options = new OptionsBag());
    }
}
