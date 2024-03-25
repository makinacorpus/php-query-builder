<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputTypeGuesser;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Type\Type;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID input converter using ramsey/uuid.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class RamseyUuidInputConverter implements InputConverter, InputTypeGuesser
{
    #[\Override]
    public function supportedInputTypes(): array
    {
        return [
            'guid',
            'uniqueidentifier',
            'uuid',
            UuidInterface::class,
        ];
    }

    #[\Override]
    public function guessInputType(mixed $value): null|string|Type
    {
        if ($value instanceof UuidInterface) {
            return Type::uuid();
        }
        return null;
    }

    #[\Override]
    public function toSql(Type $type, mixed $value, ConverterContext $context): null|int|float|string
    {
        if (!$value instanceof UuidInterface) {
            throw UnexpectedInputValueTypeError::create(UuidInterface::class, $value);
        }
        return $value->toString();
    }
}
