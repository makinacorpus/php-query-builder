<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

class ObjectId
{
    const TYPE_COLUMN = 'column';
    const TYPE_FOREIGN_KEY = 'fkey';
    const TYPE_INDEX = 'index';
    const TYPE_KEY = 'key';
    const TYPE_TABLE = 'table';

    /** Internal string representation of the identifier. */
    private ?string $repr = null;

    public function __construct(
        private readonly string $database,
        private readonly string $type,
        private readonly string $name,
        /** For a column, for example, the table name. */
        private readonly ?string $namespace = null,
        private readonly ?string $schema = 'public',
        /** For example, when using PostgreSQL, the OID value. */
        private readonly ?string $vendorId = null,
    ) {}

    /**
     * Get relative name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get type.
     */
    public function getObjectType(): string
    {
        return $this->type;
    }

    /**
     * Get type.
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * Get type.
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Get relation name it's into.
     */
    protected function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Get type.
     */
    public function getVendorId(): ?string
    {
        return $this->vendorId;
    }

    /**
     * Is equal to.
     */
    public function equals(ObjectId $other): bool
    {
        return ($this->vendorId && $other->vendorId) ? $this->vendorId === $other->vendorId : $this->toString() === $other->toString();
    }

    /**
     * Compute unique and reproducible string representation.
     */
    private function computeRepr(): string
    {
        return $this->type . ':' . $this->database . '.' . $this->schema . ($this->namespace ? '.' . $this->namespace : '') . '.' . $this->name;
    }

    /**
     * Get unique and reproducible string representation.
     */
    public function toString(): string
    {
        return $this->repr ??= $this->computeRepr();
    }

    /**
     * Get unique and reproducible string representation.
     */
    public final function __toString(): string
    {
        return $this->toString();
    }
}
