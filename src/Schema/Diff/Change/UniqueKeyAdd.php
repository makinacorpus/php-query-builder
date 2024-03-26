<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Create a UNIQUE constraint on a table.
 */
class UniqueKeyAdd extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        private readonly ?string $name = null,
        private readonly bool $nullsDistinct = true,
    ) {
        parent::__construct(schema: $schema);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function isNullsDistinct(): bool
    {
        return $this->nullsDistinct;
    }

    /**
     * Used in edge cases, for example when you CREATE INDEX in MySQL,
     * it requires you to give an index name, but this API doesn't
     * because almost all RDBMS will generate one for you. This is not
     * part of the API, it simply help a very few of those edge cases
     * not breaking.
     */
    public function generateName(): string
    {
        $pieces = [];
        $pieces[] = $this->table;
        $pieces[] = \implode('_', $this->columns);
        $pieces[] = 'key';

        return \implode('_', \array_filter($pieces));
    }
}
