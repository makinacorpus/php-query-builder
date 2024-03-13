<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\Converter\InputConverter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

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
    public function toSql(string $type, mixed $value, ConverterContext $context): null|int|float|string
    {
        // Convert some known types to Doctrine own type system.
        $type = match ($type) {
            'bigint' => Types::BIGINT,
            'bigserial' => Types::BIGINT,
            'blob' => Types::BINARY,
            'bool' => Types::BOOLEAN,
            'boolean' => Types::BOOLEAN,
            'bytea' => Types::BINARY,
            'char' => Types::STRING,
            'character' => Types::STRING,
            'decimal' => Types::DECIMAL,
            'double' => Types::FLOAT,
            'float' => Types::FLOAT,
            'float4' => Types::FLOAT,
            'float8' => Types::FLOAT,
            'int' => Types::BIGINT,
            'int2' => Types::SMALLINT,
            'int4' => Types::INTEGER,
            'int8' => Types::BIGINT,
            'integer' => Types::INTEGER,
            'json' => Types::JSON,
            'jsonb' => Types::JSON,
            'numeric' => Types::DECIMAL,
            'real' => Types::FLOAT,
            'serial' => Types::INTEGER,
            'serial2' => Types::SMALLINT,
            'serial4' => Types::INTEGER,
            'serial8' => Types::BIGINT,
            'smallint' => Types::SMALLINT,
            'smallserial' => Types::SMALLINT,
            'string' => Types::STRING,
            'text' => Types::TEXT,
            'varchar' => Types::TEXT,
            default => $type,
        };

        try {
            return Type::getType($type)->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
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
