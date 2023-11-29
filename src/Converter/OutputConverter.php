<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

/**
 * Convert an SQL formatted value to a PHP value.
 */
interface OutputConverter extends ConverterPlugin
{
    /**
     * Get supported PHP types.
     *
     * @return array<string>
     *   If you return ['*'], the input converted will be dynamically called
     *   late if no other was able to deal with the given type as a fallback.
     */
    public function supportedOutputTypes(): array;

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
    public function fromSql(string $type, int|float|string $value, ConverterContext $context): mixed;
}
