<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Converter\InputConverter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type as DbalType;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Passthough value conversion to doctrine/dbal own machinery.
 */
class DoctrineInputConverter implements InputConverter
{
    public function __construct(
        private Connection $connection,
    ) {}

    #[\Override]
    public function supportedInputTypes(): array
    {
        return ['*'];
    }

    #[\Override]
    public function toSql(Type $type, mixed $value, ConverterContext $context): null|int|float|string
    {
        // Convert some known types to Doctrine own type system.
        $type = match ($type->internal) {
            InternalType::BINARY => Types::BINARY,
            InternalType::BOOL => Types::BOOLEAN,
            InternalType::CHAR => Types::STRING,
            // InternalType::DATE => Types::DATE_IMMUTABLE,
            // InternalType::DATE_INTERVAL => $this->getDateIntervalType(),
            InternalType::DECIMAL => Types::DECIMAL,
            InternalType::FLOAT => Types::FLOAT,
            InternalType::FLOAT_BIG => Types::FLOAT,
            InternalType::FLOAT_SMALL => Types::FLOAT,
            InternalType::IDENTITY => Types::INTEGER,
            InternalType::IDENTITY_BIG => Types::BIGINT,
            InternalType::IDENTITY_SMALL => Types::SMALLINT,
            InternalType::INT => Types::INTEGER,
            InternalType::INT_BIG => Types::BIGINT,
            InternalType::INT_SMALL => Types::SMALLINT,
            InternalType::JSON => Types::JSON,
            // InternalType::NULL => 'null',
            InternalType::SERIAL => Types::INTEGER,
            InternalType::SERIAL_BIG => Types::BIGINT,
            InternalType::SERIAL_SMALL => Types::SMALLINT,
            InternalType::TEXT => Types::STRING,
            // InternalType::TIME => $this->getTimeType(),
            // InternalType::TIMESTAMP => $this->getTimestampType(),
            InternalType::VARCHAR => Types::STRING,
            // InternalType::UUID => $this->getUuidType(),
            // InternalType::UNKNOWN => $type->name ?? throw new QueryBuilderError("Unhandled types must have a type name."),
            default => $value,
            default => $type,
        };

        try {
            return DbalType::getType($type)->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
        } catch (ConversionException $e) {
            throw new ValueConversionError($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            if (\str_contains($e->getMessage(), 'column type')) {
                throw new ValueConversionError($e->getMessage(), 0, $e);
            }
            throw $e;
        }
    }
}
