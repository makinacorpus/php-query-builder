<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Create a UNIQUE constraint on a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class UniqueKeyAdd extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        /** @var string */
        private readonly null|string $name = null,
        /** @var bool */
        private readonly bool $nullsDistinct = true,
    ) {
        parent::__construct(
            database: $database,
            schema: $schema,
        );
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return string */
    public function getName(): null|string
    {
        return $this->name;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return bool */
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
