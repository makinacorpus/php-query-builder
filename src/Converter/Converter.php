<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Converter\Helper\ArrayRowParser;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;

class Converter
{
    private ?ConverterPluginRegistry $converterPluginRegistry = null;

    /**
     * Set converter plugin registry.
     *
     * @internal
     *   For dependency injection usage. This is what allows global converter
     *   plugins configuration in Symfony bundle, for example, and sharing
     *   user configuration to all databases connections.
     */
    public function setConverterPluginRegistry(ConverterPluginRegistry $converterPluginRegistry): void
    {
        $this->converterPluginRegistry = $converterPluginRegistry;
    }

    /**
     * Get converter plugin registry.
     */
    protected function getConverterPluginRegistry(): ConverterPluginRegistry
    {
        return $this->converterPluginRegistry ??= new ConverterPluginRegistry();
    }

    /** @todo This needs cleanup. */
    protected function map(mixed $values, callable $function): iterable
    {
        return \array_map($function, \is_array($values) ? $values : (\is_iterable($values) ? \iterator_to_array($values) : [$values]));
    }

    /** @todo This needs cleanup. */
    protected function join(mixed $values, string $separator = '', null|string|callable $function = null): string
    {
        if (!\is_iterable($values)) {
            $values = [$values];
        }
        $ret = '';
        $first = true;
        foreach ($values as $value) {
            if ($first) {
                $first = false;
            } else {
                $ret .= $separator;
            }
            if (\is_string($function)) {
                $ret .= $function;
            } else if (\is_callable($function)) {
                $ret .= (string) $function($value);
            } else {
                $ret .= (string) $value;
            }
        }
        return $ret;
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
    public function toExpression(mixed $value, ?string $type = null): Expression
    {
        if (null === $value) {
            return ExpressionFactory::null();
        }

        if ($value instanceof Expression) {
            return $value;
        }

        // @todo This needs cleanup.
        if ($type && \str_ends_with($type, '[]')) {
            $valueType = \substr($type, 0, -2);

            return match ($valueType) {
                'array' => throw new ValueConversionError("ARRAY[ARRAY] is not supported yet."),
                'column' => throw new ValueConversionError("ARRAY[column_expr] is not supported yet."),
                'identifier' => ExpressionFactory::raw($this->join($value, ',', fn () => '?'), $this->map($value, ExpressionFactory::identifier(...))),
                'row' => ExpressionFactory::array($this->map($value, ExpressionFactory::row(...))),
                'table' => throw new ValueConversionError("ARRAY[column_expr] is not supported yet."),
                'value' => ExpressionFactory::array($value),
                default => ExpressionFactory::array($value, $valueType),
            };
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
     * Convert SQL formatted value to given PHP type.
     *
     * You may return null for null values.
     *
     * @return mixed
     *
     * @throws ValueConversionError
     *   In case of value conversion error.
     */
    public function fromSql(string $type, int|float|string $value): mixed
    {
        if (null === $value) {
            return null;
        }

        // @todo Maybe later, too slow for hydration. Never do this automatically.
        // if (null === $type) {
        //     $type = $this->guessOutputType($value);
        // }

        if (\str_ends_with($type, '[]')) {
            return $this->parseArrayRecursion(\substr($type, 0, -2), ArrayRowParser::parseArray($value));
        }

        try {
            return $this->fromSqlUsingPlugins($value, $type);
        } catch (ValueConversionError) {}

        try {
            return $this->fromSqlUsingPlugins($value, '*');
        } catch (ValueConversionError) {}

        // Calling default implementation after plugins allows API users to
        // override default behavior and implement their own logic pretty
        // much everywhere.
        return $this->fromSqlDefault($type, $value);
    }

    /**
     * Proceed to naive PHP type conversion.
     */
    public function guessInputType(mixed $value): string
    {
        if (\is_object($value)) {
            foreach ($this->getConverterPluginRegistry()->getTypeGuessers() as $plugin) {
                \assert($plugin instanceof InputTypeGuesser);

                if ($type = $plugin->guessInputType($value)) {
                    return $type;
                }
            }
        }

        return \get_debug_type($value);
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
            $type = $this->guessInputType($value);
        }

        if (\str_ends_with($type, '[]')) {
            // @todo Handle array.
            throw new ValueConversionError(\sprintf("Handling arrays is not implemented yet, found type '%s'.", $type));
        }

        try {
            return $this->toSqlUsingPlugins($value, $type);
        } catch (ValueConversionError) {}

        try {
            return $this->toSqlUsingPlugins($value, '*', $type);
        } catch (ValueConversionError) {}

        // Calling default implementation after plugins allows API users to
        // override default behavior and implement their own logic pretty
        // much everywhere.
        return $this->toSqlDefault($type, $value);
    }

    /**
     * Parse SQL ARRAY output recursively.
     */
    protected function parseArrayRecursion(?string $type, array $values): array
    {
        return \array_map(
            fn (mixed $value) => \is_array($value) ? $this->parseArrayRecursion($type, $value) : $this->fromSql($type, $value),
            $values,
        );
    }

    /**
     * Allow bridge specific implementations to create their own context.
     */
    protected function getConverterContext(): ConverterContext
    {
        return new ConverterContext($this);
    }

    /**
     * Run all plugins to convert a value.
     */
    protected function fromSqlUsingPlugins(null|int|float|string|object $value, string $type, ?string $realType = null): mixed
    {
        $realType ??= $type;
        $context = $this->getConverterContext();

        foreach ($this->getConverterPluginRegistry()->getOutputConverters($type) as $plugin) {
            \assert($plugin instanceof OutputConverter);

            try {
                return $plugin->fromSql($realType, $value, $context);
            } catch (ValueConversionError) {}
        }

        throw new ValueConversionError();
    }

    /**
     * Handles common primitive types.
     */
    protected function fromSqlDefault(string $type, null|int|float|string|object $value): mixed
    {
        return match ($type) {
            'bool' => \is_int($value) ? (bool) $value : ((!$value || 'f' === $value || 'F' === $value || 'false' === \strtolower($value)) ? false : true),
            'float' => (float) $value,
            'int' => (int) $value,
            'json' => \json_decode($value, true),
            'string' => (string) $value,
            default => throw new ValueConversionError(\sprintf("Unhandled PHP type '%s'", $type)),
        };
    }

    /**
     * Run all plugins to convert a value.
     */
    protected function toSqlUsingPlugins(mixed $value, string $type, ?string $realType = null): null|int|float|string|object
    {
        $realType ??= $type;
        $context = $this->getConverterContext();

        foreach ($this->getConverterPluginRegistry()->getInputConverters($type) as $plugin) {
            \assert($plugin instanceof InputConverter);

            try {
                return $plugin->toSql($realType, $value, $context);
            } catch (ValueConversionError) {}
        }

        throw new ValueConversionError();
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
            'bool' => $value ? 'true' : 'false',
            'boolean' => $value ? 'true' : 'false',
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
            'varchar' => (string) $value,
            default => $value,
        };
    }
}
