<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

class SqlString
{
    public function __construct(
        private string $rawSql,
        private ?ArgumentBag $arguments = null,
        private ?string $identifier = null,
        private ?OptionsBag $options = null,
    ) {}

    /**
     * Get user given query arguments.
     */
    public function getArguments(): ArgumentBag
    {
        return $this->arguments ??= new ArgumentBag();
    }

    /**
     * Get query propagated options.
     */
    public function getOptions(): OptionsBag
    {
        return $this->options ?? new OptionsBag([]);
    }

    /**
     * Get query identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier ??= 'mcqb_' . \md5($this->rawSql);
    }

    /**
     * Get generated SQL code.
     */
    public function toString(): string
    {
        return $this->rawSql;
    }

    /**
     * Get generated SQL code.
     */
    public function __toString(): string
    {
        return $this->rawSql;
    }
}
