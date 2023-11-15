<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID input converter using ramsey/uuid.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class RamseyUuidInputConverter implements InputConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            'uuid',
            UuidInterface::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function guessInputType(mixed $value): ?string
    {
        if ($value instanceof UuidInterface) {
            return 'uuid';
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function toSql(string $type, mixed $value, ConverterContext $context): null|string
    {
        if (!$value instanceof UuidInterface) {
            throw UnexpectedInputValueTypeError::create(UuidInterface::class, $value);
        }

        return $value->toString();
    }
}
