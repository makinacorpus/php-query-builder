<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

/**
 * UUID/ULID input converter using symfony/uid.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class SymfonyUidInputConverter implements InputConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            'ulid',
            'uuid',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function guessInputType(mixed $value): ?string
    {
        if ($value instanceof Uuid) {
            return 'uuid';
        }
        if ($value instanceof AbstractUid) {
            return 'ulid';
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function toSql(string $type, mixed $value, ConverterContext $context): null|string
    {
        if (!$value instanceof AbstractUid) {
            throw UnexpectedInputValueTypeError::create(AbstractUid::class, $value);
        }

        return (string) $value;
    }
}
