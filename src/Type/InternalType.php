<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

/**
 * Core supported types.
 *
 * All Type instances will carry a case of this enum. If the current case is
 * "unknown" then the type isn't directly managed in writer, "name" property
 * will be propagated as a raw string to the database vendor instead for
 * column creation or type cast in query writer.
 */
enum InternalType
{
    /** Binary. */
    case BINARY;

    /** Boolean. */
    case BOOL;

    /** Character. */
    case CHAR;

    /** Date without time. */
    case DATE;

    /** Date interval type. */
    case DATE_INTERVAL;

    /** Arbitrary precision number. */
    case DECIMAL;

    /** Floating point number. */
    case FLOAT;

    /** Big floating point number. */
    case FLOAT_BIG;

    /** Small floating point number. */
    case FLOAT_SMALL;

    /** Identity. */
    case IDENTITY;

    /** Big identity. */
    case IDENTITY_BIG;

    /** Small identity. */
    case IDENTITY_SMALL;

    /** Integer. */
    case INT;

    /** Big integer. */
    case INT_BIG;

    /** Small integer. */
    case INT_SMALL;

    /** Tiny integer. */
    case INT_TINY;

    /** JSON. */
    case JSON;

    /** NULL is not really a type. */
    case NULL;

    /** Identity (pgsql only, deprecated). */
    case SERIAL;

    /** Big identity (pgsql only, deprecated). */
    case SERIAL_BIG;

    /** Small identity (pgsql only, deprecated). */
    case SERIAL_SMALL;

    /** Arbitrary length text. */
    case TEXT;

    /** Time. */
    case TIME;

    /** Date and time. */
    case TIMESTAMP;

    /** Character varying. */
    case VARCHAR;

    /** Unknown / unhandled type. */
    case UNKNOWN;

    /** UUID/GUID. */
    case UUID;

    /**
     * Find internal type from name.
     */
    public static function fromTypeName(string $userType): InternalType
    {
        switch ($userType) {

            /*
             * Boolean types.
             */

            case 'bool':
            case 'boolean':
                return InternalType::BOOL;

            /*
             * Numeric types
             */

            case 'tinyint':
                return InternalType::INT_TINY;

            case 'int2': // PostgreSQL
            case 'smallint':
                return InternalType::INT_SMALL;

            case 'int':
            case 'int4': // PostgreSQL
            case 'integer':
                return InternalType::INT;

            case 'bigint':
            case 'int8': // PostgreSQL
                return InternalType::INT_BIG;

            case 'decimal':
            case 'numeric':
                return InternalType::DECIMAL;

            case 'float2': // PostgreSQL
            case 'smallfloat':
                return InternalType::FLOAT_SMALL;

            case 'double':
            case 'float':
            case 'float4': // PostgreSQL
            case 'real':
                return InternalType::FLOAT;

            case 'bigfloat':
            case 'float8': // PostgreSQL
                return InternalType::FLOAT_BIG;

            /*
             * Identity types (mostly numeric).
             */

            case 'smallid':
            case 'smallidentity':
                return InternalType::IDENTITY_SMALL;

            case 'id':
            case 'identity':
                return InternalType::IDENTITY;

            case 'bigid':
            case 'bigidentity':
                return InternalType::IDENTITY_BIG;

            case 'serial2': // PostgreSQL
            case 'smallserial': // PostgreSQL
                return InternalType::SERIAL_SMALL;

            case 'serial': // PostgreSQL
            case 'serial4': // PostgreSQL
                return InternalType::SERIAL;

            case 'bigserial': // PostgreSQL
            case 'serial8': // PostgreSQL
                return InternalType::SERIAL_BIG;

            /*
             * Text types.
             */

            case 'bpchar': // PostgreSQL
            case 'char':
            case 'character':
            case 'nchar': // SQL Server
                return InternalType::CHAR;

            case 'ntext': // SQL Server
            case 'string':
            case 'text':
                return InternalType::TEXT;

            case 'char varying':
            case 'character varying':
            case 'nvarchar': // SQL Server
            case 'varchar':
            case 'varchar2': // Oracle
            case 'varying character': // SQLite
                return InternalType::VARCHAR;

            /*
             * Binary types.
             */

            case 'binary': // SQL Server
            case 'blob':
            case 'bytea': // PostgreSQL
            case 'varbinary':
                return InternalType::BINARY;

            /*
             * Date and time.
             */

            case 'interval':
                return InternalType::DATE_INTERVAL;

            case 'date':
                return InternalType::DATE;

            case 'time': // PostgreSQL
                return InternalType::TIME;

            case 'timez':
                return InternalType::TIME;

            case 'datetime':
            case 'datetime2': // SQL Server
            case 'datetimez': // PostgreSQL
            case 'timestamp':
            case 'timestampz': // PostgreSQL
                return InternalType::TIMESTAMP;

            /*
             * Specific types.
             */

            case 'json':
            case 'jsonb': // PostgreSQL
                return InternalType::JSON;

            case 'guid':
            case 'uniqueidentifier':
            case 'uuid':
                return InternalType::UUID;

            /*
             * Unknown type.
             */

            default:
                return InternalType::UNKNOWN;
        }
    }

    public function precisionIsRelevant(): bool
    {
        return $this === self::DECIMAL;
    }

    public function scaleIsRelevant(): bool
    {
        return $this === self::DECIMAL;
    }

    public function lengthIsRelevant(): bool
    {
        return $this === self::CHAR || $this === self::VARCHAR;
    }

    public function isDate(): bool
    {
        return $this === self::DATE || $this === self::TIMESTAMP;
    }

    public function isIdentity(): bool
    {
        return $this === self::IDENTITY || $this === self::IDENTITY_BIG || $this === self::IDENTITY_SMALL;
    }

    public function isNumeric(): bool
    {
        return
            $this === self::DECIMAL ||
            $this === self::FLOAT || $this === self::FLOAT_BIG || $this === self::FLOAT_SMALL ||
            $this === self::INT || $this === self::INT_BIG || $this === self::INT_SMALL || $this === self::INT_TINY ||
            $this === self::IDENTITY || $this === self::IDENTITY_BIG || $this === self::IDENTITY_SMALL ||
            $this === self::SERIAL || $this === self::SERIAL_BIG || $this === self::SERIAL_SMALL
        ;
    }

    public function isSerial(): bool
    {
        return $this === self::SERIAL || $this === self::SERIAL_BIG || $this === self::SERIAL_SMALL;
    }

    public function isText(): bool
    {
        return $this === self::CHAR || $this === self::TEXT || $this === self::VARCHAR;
    }
}
