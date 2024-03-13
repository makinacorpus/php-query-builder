<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

/**
 * Stores arbitrary options.
 */
final class OptionsBag
{
    public function __construct(
        private array $data = [],
    ) {}

    public function get(string $name, mixed $default = null): mixed
    {
        return \array_key_exists($name, $this->data) ? $this->data[$name] : $default;
    }

    public function getAll(?array $overrides = null): array
    {
        return $overrides ? \array_merge($this->data, $overrides) : $this->data;
    }

    public function set(string $name, mixed $value): static
    {
        if (null === $value) {
            unset($this->data[$name]);
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }

    public function setAll(array $options): static
    {
        foreach ($options as $name => $value) {
            $this->set($name, $value);
        }
        return $this;
    }
}
