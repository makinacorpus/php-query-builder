<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

/**
 * Represent internally any type, with length or precision and scale.
 */
class Type
{
    public readonly bool $isDate;
    public readonly bool $isNumeric;
    public readonly bool $isText;

    public function __construct(
        public InternalType $internal,
        public ?string $name = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $withTimeZone = false,
        public bool $array = false,
    ) {
        if ($internal === InternalType::UNKNOWN && !$name) {
            throw new QueryBuilderError("Unhandled internal types must have a type name.");
        }

        $this->isDate = $this->internal->isDate();
        $this->isNumeric = $this->internal->isNumeric();
        $this->isText = $this->internal->isText();
    }

    /**
     * Blob/binary data.
     */
    public static function binary(): self
    {
        return new self(internal: InternalType::BINARY);
    }

    /**
     * Boolean.
     */
    public static function bool(): self
    {
        return new self(internal: InternalType::BOOL);
    }

    /**
     * Padded fixed-with character string.
     */
    public static function char(int $length = null): self
    {
        return new self(internal: InternalType::CHAR, length: $length);
    }

    /**
     * Date without time.
     */
    public static function date(): self
    {
        return new self(internal: InternalType::DATE);
    }

    /**
     * Date interval.
     *
     * Warning: only supported by PostgreSQL.
     */
    public static function dateInterval(): self
    {
        return new self(internal: InternalType::DATE_INTERVAL);
    }

    /**
     * Decimal/numeric arbitrary precision numeric value.
     */
    public static function decimal(?int $precision = null, int $scale = null): self
    {
        return new self(internal: InternalType::DECIMAL, precision: $precision, scale: $scale);
    }

    /**
     * Floating point numeric value.
     */
    public static function float(): self
    {
        return new self(internal: InternalType::FLOAT);
    }

    /**
     * Floating point numeric value (big).
     *
     * Warning: per default is simply a float, only PostgreSQL allows the big
     * and small variants.
     */
    public static function floatBig(): self
    {
        return new self(internal: InternalType::FLOAT_BIG);
    }

    /**
     * Floating point numeric value (small).
     *
     * Warning: per default is simply a float, only PostgreSQL allows the big
     * and small variants.
     */
    public static function floatSmall(): self
    {
        return new self(internal: InternalType::FLOAT_SMALL);
    }

    /**
     * Auto-generated identity (integer).
     */
    public static function identity(): self
    {
        return new self(internal: InternalType::IDENTITY);
    }

    /**
     * Auto-generated identity (integer, big).
     */
    public static function identityBig(): self
    {
        return new self(internal: InternalType::IDENTITY_BIG);
    }

    /**
     * Auto-generated identity (integer, small).
     */
    public static function identitySmall(): self
    {
        return new self(internal: InternalType::IDENTITY_SMALL);
    }

    /**
     * Integer.
     */
    public static function int(): self
    {
        return new self(internal: InternalType::INT);
    }

    /**
     * Integer (big).
     */
    public static function intBig(): self
    {
        return new self(internal: InternalType::INT_BIG);
    }

    /**
     * Integer (small).
     */
    public static function intSmall(): self
    {
        return new self(internal: InternalType::INT_SMALL);
    }

    /**
     * JSON data.
     *
     * Warning: will fallback on "text" type for most vendors.
     */
    public static function json(): self
    {
        return new self(internal: InternalType::JSON);
    }

    /**
     * Simply NULL.
     */
    public static function null(): self
    {
        return new self(internal: InternalType::NULL);
    }

    /**
     * Raw type name, that will propagated as-is to the database.
     *
     * Types ending with [] will see it stripped, but will be marked as array.
     * Types ending with (X) where X is numeric will have a length.
     * Types ending with (X,Y) where X and Y are numeric will have a precision
     * and a scale.
     */
    public static function raw(string $name): self
    {
        return self::create($name, true);
    }

    /**
     * Auto-generated identity (integer).
     *
     * @deprecated
     *   PostgreSQL-only legacy pre-IDENTITY SQL standard variant.
     */
    public static function serial(): self
    {
        return new self(internal: InternalType::SERIAL);
    }

    /**
     * Auto-generated identity (integer, big).
     *
     * @deprecated
     *   PostgreSQL-only legacy pre-IDENTITY SQL standard variant.
     */
    public static function serialBig(): self
    {
        return new self(internal: InternalType::SERIAL_BIG);
    }

    /**
     * Auto-generated identity (integer, small).
     *
     * @deprecated
     *   PostgreSQL-only legacy pre-IDENTITY SQL standard variant.
     */
    public static function serialSmall(): self
    {
        return new self(internal: InternalType::SERIAL_SMALL);
    }

    /**
     * Simply text.
     */
    public static function text(): self
    {
        return new self(internal: InternalType::TEXT);
    }

    /**
     * Time without a date.
     */
    public static function time(): self
    {
        return new self(internal: InternalType::TIME);
    }

    /**
     * Timestamp (known as datetime in some vendors), with or without time zone.
     *
     * Warning: only PostgreSQL supports "with time zone" for storage. All others
     * will simply store the UTC value per default after converting input dates
     * using the session client default time zone.
     */
    public static function timestamp(bool $withTimeZone = false): self
    {
        return new self(internal: InternalType::TIMESTAMP, withTimeZone: $withTimeZone);
    }

    /**
     * Character varying text, variable length with maximum size limit.
     */
    public static function varchar(?int $length = null): self
    {
        return new self(internal: InternalType::VARCHAR, length: $length);
    }

    /**
     * Convert given user input type to vendor type.
     */
    public static function create(string|self $userType, bool $raw = false): self
    {
        if ($userType instanceof self) {
            return $userType;
        }

        $userType = \trim($userType);
        $array = $withTimeZone = false;
        $name = $length = $precision = $scale = null;

        if (\str_ends_with($userType, '[]')) {
            $userType = \trim(\substr($userType, 0, -2));
            $array = true;
        }

        // Catch precision and scale or length.
        $matches = [];
        if (\preg_match('/(.*)\((\d+)(|,(\d+))\)$/', $userType, $matches)) {
            if ($matches[2]) {
                if ($matches[3]) {
                    $precision = (int) $matches[2];
                    $scale = (int) $matches[4];
                } else {
                    $length = (int) $matches[2];
                }
            }
            $userType = \trim($matches[1]);
        }

        if ($raw) {
            return new Type(
                array: $array,
                internal: InternalType::UNKNOWN,
                length: $length,
                name: $userType,
                precision: $precision,
                scale: $scale,
                withTimeZone: $withTimeZone,
            );
        }

        if (\str_ends_with($userType, 'without time zone')) {
            $userType = \trim(\substr($userType, 0, -17));
        } else if (\str_ends_with($userType, 'with time zone')) {
            $userType = \trim(\substr($userType, 0, -14));
            $withTimeZone = true;
        }

        switch ($userType) {

            /*
             * Boolean types.
             */

            case 'bool':
            case 'boolean':
                $internal = InternalType::BOOL;
                break;

            /*
             * Numeric types
             */

            case 'int2': // PostgreSQL
            case 'int4': // PostgreSQL
            case 'smallint':
                $internal = InternalType::INT_SMALL;
                break;

            case 'int':
            case 'integer':
                $internal = InternalType::INT;
                break;

            case 'bigint':
            case 'int8': // PostgreSQL
                $internal = InternalType::INT_BIG;
                break;

            case 'decimal':
            case 'numeric':
                $internal = InternalType::DECIMAL;
                break;

            case 'float2': // PostgreSQL
            case 'float4': // PostgreSQL
            case 'smallfloat':
                $internal = InternalType::FLOAT_SMALL;
                break;

            case 'double':
            case 'float':
            case 'real':
                $internal = InternalType::FLOAT;
                break;

            case 'bigfloat':
            case 'float8': // PostgreSQL
                $internal = InternalType::FLOAT_BIG;
                break;

            /*
             * Identity types (mostly numeric).
             */

            case 'smallid':
            case 'smallidentity':
                $internal = InternalType::IDENTITY_SMALL;
                break;

            case 'id':
            case 'identity':
                $internal = InternalType::IDENTITY;
                break;

            case 'bigid':
            case 'bigidentity':
                $internal = InternalType::IDENTITY_BIG;
                break;

            case 'serial2': // PostgreSQL
            case 'serial4': // PostgreSQL
            case 'smallserial': // PostgreSQL
                $internal = InternalType::SERIAL_SMALL;
                break;

            case 'serial': // PostgreSQL
                $internal = InternalType::SERIAL;
                break;

            case 'bigserial': // PostgreSQL
            case 'serial8': // PostgreSQL
                $internal = InternalType::SERIAL_BIG;
                break;

            /*
             * Text types.
             */

            case 'bpchar': // PostgreSQL
            case 'char':
            case 'character':
            case 'nchar': // SQL Server
                $internal = InternalType::CHAR;
                break;

            case 'ntext': // SQL Server
            case 'string':
            case 'text':
                $internal = InternalType::TEXT;
                break;

            case 'char varying':
            case 'character varying':
            case 'nvarchar': // SQL Server
            case 'varchar':
            case 'varchar2': // Oracle
            case 'varying character': // SQLite
                $internal = InternalType::VARCHAR;
                break;

            /*
             * Binary types.
             */

            case 'binary': // SQL Server
            case 'blob':
            case 'bytea': // PostgreSQL
            case 'varbinary':
                $internal = InternalType::BINARY;
                break;

            /*
             * Date and time.
             */

            case 'interval':
                $internal = InternalType::DATE_INTERVAL;
                break;

            case 'date':
                $internal = InternalType::DATE;
                break;

            case 'time': // PostgreSQL
                $internal = InternalType::TIME;
                break;

            case 'timez':
                $internal = InternalType::TIME;
                $withTimeZone = true;
                break;

            case 'datetime':
            case 'datetime2': // SQL Server
            case 'timestamp':
                $internal = InternalType::TIMESTAMP;
                break;

            case 'datetimez': // PostgreSQL
            case 'timestampz': // PostgreSQL
                $internal = InternalType::TIMESTAMP;
                $withTimeZone = true;
                break;

            /*
             * Specific types.
             */

            case 'json':
            case 'jsonb': // PostgreSQL
                $internal = InternalType::JSON;
                break;

            /*
             * Unknown type.
             */

            default:
                $name = $userType;
                $internal = InternalType::UNKNOWN;
                break;
        }

        return new Type(
            array: $array,
            internal: $internal,
            length: $length,
            name: $name,
            precision: $precision,
            scale: $scale,
            withTimeZone: $withTimeZone,
        );
    }

    public function toArray(): self
    {
        if ($this->array) {
            throw new QueryBuilderError("This API does not support more than one array dimension.");
        }

        $ret = clone $this;
        $ret->array = true;

        return $ret;
    }
}
