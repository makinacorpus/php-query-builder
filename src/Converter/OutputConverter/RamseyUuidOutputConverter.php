<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID output converter using ramsey/uuid.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class RamseyUuidOutputConverter implements OutputConverter
{
    #[\Override]
    public function supportedOutputTypes(): array
    {
        return [
            UuidInterface::class,
        ];
    }

    #[\Override]
    public function fromSql(string $type, int|float|string $value, ConverterContext $context): mixed
    {
        return Uuid::fromString($value);
    }
}
