<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

class Converter
{
    /** @var array<string,array<InputConverter>> */
    private array $inputConverters = [];
    private array $inputParsers = [];

    /** @param iterable<ConverterPlugin> $plugins */
    public function __construct(?iterable $plugins = null)
    {
        if (null === $plugins) {
            // @todo Create default.
        } else {
            foreach ($plugins as $plugin) {
                $this->register($plugin);
            }
        }
    }

    /**
     * Register a custom value converter.
     */
    public function register(ConverterPlugin $plugin): void
    {
        if ($plugin instanceof InputConverter) {
            foreach ($plugin->getSupportedTypes() as $type) {
                $this->inputConverters[$type][] = $plugin;
            }
        } else {
            throw new \InvalidArgumentException(\sprintf("Unsupported plugin class %s", \get_class($plugin)));
        }
    }

    /**
     * Convert PHP native type to an expresion in raw SQL parser.
     *
     * This will happen in Writer during raw SQL parsing when a placeholder
     * with a type cast such as `?::foo` is found.
     *
     * @throws ValueConversionError
     *   In case of value conversion error.
     */
    public function toExpression(mixed $value, ?string $type = null): ?Expression
    {
        if (null === $value) {
            return ExpressionFactory::null();
        }

        if ($value instanceof Expression) {
            return $value;
        }

        // Directly act with the parser and create expressions.
        return match ($type) {
            'array' => ExpressionFactory::array($value),
            'column' => ExpressionFactory::column($value),
            'identifier' => ExpressionFactory::identifier($value),
            'row' => ExpressionFactory::row($value),
            'table' => ExpressionFactory::table($value),
            'value' => ExpressionFactory::value($value),
            default => ExpressionFactory::value($value, $type),
        };
    }

    /**
     * Convert PHP native value to given SQL type.
     *
     * This will happen in ArgumentBag when values are fetched prior being
     * sent to the bridge for querying.
     *
     * @return null|int|float|string
     *   Because the underlaying driver might do implicit type cast on a few
     *   PHP native types (coucou PDO) we are doing to return those types when
     *   matched (int and float only).
     *   This should over 90% of use cases transparently. If you pass PHP
     *   strings the native driver might sometime give 'text' to the remote
     *   RDBMS, which will cause type errors on the server side.
     *
     * @throws ValueConversionError
     *   In case of value conversion error.
     */
    public function toSql(mixed $value, ?string $type = null): null|int|float|string|object
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Expression) {
            throw new ValueConversionError(\sprintf("Circular dependency detected, %s instances cannot be converted.", Expression::class));
        }

        if (null === $type) {
            $type = $this->toSqlGuessType($value);
        }

        if (\str_ends_with($type, '[]')) {
            // @todo Handle array.
            throw new ValueConversionError("Handling arrays is not implemented yet.");
        }

        $firstError = null;

        if ($plugins = ($this->inputConverters[$type] ?? null)) {
            $context = $this->getConverterContext();

            foreach ($plugins as $plugin) {
                \assert($plugin instanceof InputConverter);

                try {
                    return $plugin->toSql($type, $value, $context);
                } catch (ValueConversionError $e) {
                    if (!$firstError) {
                        $firstError = $e;
                    }
                }
            }
        }

        if ($firstError) {
            throw $firstError;
        }

        // Calling default implementation after plugins allows API users to
        // override default behavior and implement their own logic pretty
        // much everywhere.
        return $this->toSqlDefault($type, $value);
    }

    /**
     * Allow bridge specific implementations to create their own context.
     */
    protected function getConverterContext(): ConverterContext
    {
        return new ConverterContext($this);
    }

    /**
     * Proceed to naive PHP type conversion.
     */
    protected function toSqlGuessType(mixed $value): string
    {
        if (!\is_object($value)) {
            return \get_debug_type($value);
        }
        return \get_class($value);
    }

    /**
     * Handles common primitive types.
     */
    protected function toSqlDefault(string $type, mixed $value): null|int|float|string|object
    {
        return match ($type) {
            'bigint' => (int) $value,
            'bigserial' => (int) $value,
            'blob' => (string) $value,
            'bool' => $value ? 't' : 'f',
            'boolean' => $value ? 't' : 'f',
            'bytea' => (string) $value,
            'char' => (string) $value,
            'character' => (string) $value,
            'decimal' => (float) $value,
            'double' => (float) $value,
            'float' => (float) $value,
            'float4' => (float) $value,
            'float8' => (float) $value,
            'int' => (int) $value,
            'int2' => (int) $value,
            'int4' => (int) $value,
            'int8' => (int) $value,
            'integer' => (int) $value,
            'json' => \json_encode($value, true),
            'jsonb' => \json_encode($value, true),
            'numeric' => (float) $value,
            'real' => (float) $value,
            'serial' => (int) $value,
            'serial2' => (int) $value,
            'serial4' => (int) $value,
            'serial8' => (int) $value,
            'smallint' => (int) $value,
            'smallserial' => (int) $value,
            'string' => (string) $value,
            'text' => (string) $value,
            /* uuid' => if ($this->supportsUuid()) { return Uuid::fromString($value); } return (string) $value; */
            'varchar' => (string) $value,
            default => $value,
        };
    }
}
