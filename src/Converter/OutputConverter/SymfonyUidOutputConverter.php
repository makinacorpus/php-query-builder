<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * UUID/ULID output converter using symfony/uid.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class SymfonyUidOutputConverter implements OutputConverter
{
    #[\Override]
    public function supportedOutputTypes(): array
    {
        return [
            Uuid::class,
            Ulid::class,
        ];
    }

    #[\Override]
    public function fromSql(string $type, int|float|string $value, ConverterContext $context): mixed
    {
        if ($type === Ulid::class || \is_subclass_of($type, Ulid::class)) {
            return Ulid::fromString($value);
        }

        return Uuid::fromString($value);
    }
}
