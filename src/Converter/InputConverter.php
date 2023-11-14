<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

/**
 * Convert a PHP value to an SQL formatted value.
 */
interface InputConverter extends ConverterPlugin
{
    /**
     * Get supported SQL types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array;

    /**
     * Convert PHP native value to given SQL type.
     *
     * You may return null as a shortcut to SQL null value.
     *
     * @return null|string|Expression
     *   Null means SQL NULL, no questions asked.
     *   If a string is returned, this raw SQL string will be injected without
     *   any escaping, please be strict in escaping properly your values.
     *   
     *
     * @throws ValueConversionError
     *   In case of value conversion error.
     */
    public function toSql(string $type, mixed $value, ConverterContext $context): null|string|Expression;
}
