<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Create an INDEX on a table.
 */
class IndexCreate extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        private readonly ?string $name = null,
        private readonly ?string $type = null,
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

    public function getType(): ?string
    {
        return $this->type;
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
        $pieces[] = 'idx';

        return \implode('_', \array_filter($pieces));
    }
}
