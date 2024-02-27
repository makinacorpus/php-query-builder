<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterContext;
use Goat\Converter\DynamicInputValueConverter;
use Goat\Converter\DynamicOutputValueConverter;
use Goat\Converter\TypeConversionError;

/**
 * PostgreSQL array converter, compatible with SQL standard.
 */
class PgSQLArrayConverter implements DynamicInputValueConverter, DynamicOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportsOutput(?string $phpType, ?string $sqlType, string $value): bool
    {
        return 'array' === $phpType || ($sqlType && (\str_ends_with($sqlType,  '[]') || \str_starts_with($sqlType,  '_')));
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        if ('' === $value || '{}' === $value) {
            return [];
        }

        return $this->recursiveFromSQL($this->findSubtype($sqlType) ?? 'varchar', PgSQLParser::parseArray($value), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsInput(string $sqlType, mixed $value): bool
    {
        return \is_array($value) && (\str_ends_with($sqlType,  '[]') || \str_starts_with($sqlType,  '_') || \str_starts_with($value, '{'));
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $sqlType, mixed $value, ConverterContext $context): ?string
    {
        if (!\is_array($value)) {
            throw new TypeConversionError("Value must be an array.");
        }
        if (empty($value)) {
            return '{}';
        }

        $converter = $context->getConverter();
        $subType = $this->findSubtype($sqlType);

        return PgSQLParser::writeArray(
            $value,
            fn ($value) => $converter->toSQL($value, $subType),
        );
    }

    private function recursiveFromSQL(?string $sqlType, array $values, ConverterContext $context): array
    {
        $converter = $context->getConverter();

        return \array_map(
            fn ($value) => (\is_array($value) ?
                $this->recursiveFromSQL($sqlType, $value, $context) :
                // @todo Is there any way to be deterministic with PHP value type?
                $converter->fromSQL($value, $sqlType, null)
            ),
            $values
        );
    }

    private function findSubtype(?string $type): ?string
    {
        if ($type) {
            if (\str_ends_with($type, '[]')) {
                return \substr($type, 0, -2);
            }
            if (\str_starts_with($type, '_')) {
                return \substr($type, 1);
            }
        }
        return null;
    }
}
