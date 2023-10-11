<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

class SqlString
{
    public function __construct(
        private string $rawSql,
        private ?ArgumentBag $arguments = null,
        private ?string $identifier = null,
    ) {}

    public function getArguments(): ArgumentBag
    {
        return $this->arguments ?? ($this->arguments = new ArgumentBag());
    }

    public function getIdentifier(): string
    {
        return $this->identifier ?? ($this->identifier = 'mcqb_' . \md5($this->rawSql));
    }

    public function toString(): string
    {
        return $this->rawSql;
    }

    public function __toString(): string
    {
        return $this->rawSql;
    }
}
