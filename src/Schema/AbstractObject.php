<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

abstract class AbstractObject extends ObjectId
{
    public function __construct(
        string $database,
        string $type,
        string $name,
        ?string $namespace,
        string $schema,
        ?string $vendorId,
        private readonly ?string $comment,
        /**
         * Options at the discretion of the implementation.
         *
         * @var array<string,string>
         */
        public readonly array $options,
    ) {
        parent::__construct(
            database: $database,
            name: $name,
            namespace: $namespace,
            schema: $schema,
            type: $type,
            vendorId: $vendorId,
        );
    }

    /**
     * Get comment if any.
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Get arbitrary given extra options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
