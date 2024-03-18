<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

/**
 * Convert user given types to vendor-specific types.
 *
 * This also carries type conversion logic from and to SQL for most commonly
 * used standard types: this allows the writer to plug itself over the
 * converter and vendor-specific conversion to exist.
 *
 * This type handling method only supports SQL standard basic types, most
 * common to all vendors. For specifiy type operations, it will simply let
 * the user given types pass.
 */
class TypeConverter
{
    /**
     * Convert given user input type to vendor type.
     */
    public function create(string|Type $userType): Type
    {
        if ($userType instanceof Type) {
            return $userType;
        }

        $userType = \trim($userType);
        $withTimeZone = $array = false;
        $length = $precision = $scale = null;

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
                $name = $this->getBoolType();
                $internal = InternalType::BOOL;
                break;

            /*
             * Numeric types
             */

            case 'int2': // PostgreSQL
            case 'int4': // PostgreSQL
            case 'smallint':
                $name = $this->getSmallIntType();
                $internal = InternalType::INT_SMALL;
                break;

            case 'int':
            case 'integer':
                $name = $this->getIntType();
                $internal = InternalType::INT;
                break;

            case 'bigint':
            case 'int8': // PostgreSQL
                $name = $this->getBigIntType();
                $internal = InternalType::INT_BIG;
                break;

            case 'decimal':
            case 'numeric':
                $name = $this->getDecimalType();
                $internal = InternalType::DECIMAL;
                break;

            case 'float2': // PostgreSQL
            case 'float4': // PostgreSQL
            case 'smallfloat':
                $name = $this->getSmallFloatType();
                $internal = InternalType::FLOAT_SMALL;
                break;

            case 'double':
            case 'float':
            case 'real':
                $name = $this->getFloatType();
                $internal = InternalType::FLOAT;
                break;

            case 'bigfloat':
            case 'float8': // PostgreSQL
                $name = $this->getBigFloatType();
                $internal = InternalType::FLOAT_BIG;
                break;

            /*
             * Identity types (mostly numeric).
             */

            case 'smallid':
            case 'smallidentity':
                $name = $this->getSmallIdentityType();
                $internal = InternalType::IDENTITY_SMALL;
                break;

            case 'id':
            case 'identity':
                $name = $this->getIdentityType();
                $internal = InternalType::IDENTITY;
                break;

            case 'bigid':
            case 'bigidentity':
                $name = $this->getBigIdentityType();
                $internal = InternalType::IDENTITY_BIG;
                break;

            case 'serial2': // PostgreSQL
            case 'serial4': // PostgreSQL
            case 'smallserial': // PostgreSQL
                $name = $this->getSmallSerialType();
                $internal = InternalType::SERIAL_SMALL;
                break;

            case 'serial': // PostgreSQL
                $name = $this->getSerialType();
                $internal = InternalType::SERIAL;
                break;

            case 'bigserial': // PostgreSQL
            case 'serial8': // PostgreSQL
                $name = $this->getBigSerialType();
                $internal = InternalType::SERIAL_BIG;
                break;

            /*
             * Text types.
             */

            case 'bpchar': // PostgreSQL
            case 'char':
            case 'character':
            case 'nchar': // SQL Server
                $name = $this->getCharType();
                $internal = InternalType::CHAR;
                break;

            case 'ntext': // SQL Server
            case 'string':
            case 'text':
                $name = $this->getTextType();
                $internal = InternalType::TEXT;
                break;

            case 'char varying':
            case 'character varying':
            case 'nvarchar': // SQL Server
            case 'varchar':
            case 'varchar2': // Oracle
            case 'varying character': // SQLite
                $name = $this->getVarcharType();
                $internal = InternalType::VARCHAR;
                break;

            /*
             * Binary types.
             */

            case 'binary': // SQL Server
            case 'blob':
            case 'bytea': // PostgreSQL
            case 'varbinary':
                $name = $this->getBinaryType();
                $internal = InternalType::BINARY;
                break;

            /*
             * Date and time.
             */

            case 'interval':
                $name = $this->getDateIntervalType();
                $internal = InternalType::DATE_INTERVAL;
                break;

            case 'date':
                $name = $this->getDateType();
                $internal = InternalType::DATE;
                break;

            case 'time': // PostgreSQL
                $name = $this->getTimeType();
                $internal = InternalType::TIME;
                break;

            case 'timez':
                $name = $this->getTimeType();
                $internal = InternalType::TIME;
                $withTimeZone = true;
                break;

            case 'datetime':
            case 'datetime2': // SQL Server
            case 'timestamp':
                $name = $this->getTimestampType();
                $internal = InternalType::TIMESTAMP;
                break;

            case 'datetimez': // PostgreSQL
            case 'timestampz': // PostgreSQL
                $name = $this->getTimestampType();
                $internal = InternalType::TIMESTAMP;
                $withTimeZone = true;
                break;

            /*
             * Specific types.
             */

            case 'json':
            case 'jsonb': // PostgreSQL
                $name = $this->getJsonType();
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
            internal: $internal,
            name: $name,
            length: $length,
            precision: $precision,
            scale: $scale,
            withTimeZone: $withTimeZone,
            array: $array
        );
    }

    /**
     * Is given type any kind of numeric type.
     */
    public function isTypeNumeric(string|Type $type): string
    {
        return $this->create($type)->isNumeric;
    }

    /**
     * Is given type integer.
     */
    public function isTypeIntCompatible(string|Type $type): string
    {
        return $this->create($type)->isNumeric;
    }

    /**
     * Is given type varchar.
     */
    public function isTypeVarcharCompatible(string|Type $type): string
    {
        return $this->create($type)->isText;
    }

    /**
     * Is given type date.
     */
    public function isTypeDateCompatible(string|Type $type): string
    {
        $type = $this->create($type);

        return $type->internal === InternalType::DATE || $type->internal === InternalType::TIMESTAMP;
    }

    /**
     * Is given type timestamp.
     */
    public function isTypeTimestampCompatible(string|Type $type): string
    {
        $type = $this->create($type);

        return $type->internal === InternalType::DATE || $type->internal === InternalType::TIMESTAMP;
    }

    /**
     * Is given type text.
     */
    public function isTypeTextCompatible(string|Type $type): string
    {
        return $this->create($type)->isText;
    }

    /**
     * Get big integer type.
     */
    public function getBoolType(): string
    {
        return 'bool';
    }

    /**
     * Get integer type.
     */
    public function getSmallIntType(): string
    {
        return 'smallint';
    }

    /**
     * Get integer type.
     */
    public function getIntType(): string
    {
        return 'int';
    }

    /**
     * Get big integer type.
     */
    public function getBigIntType(): string
    {
        return 'bigint';
    }

    /**
     * Get decimal/numeric/user given precision number type.
     */
    public function getDecimalType(): string
    {
        return 'decimal';
    }

    /**
     * Get float type.
     *
     * @deprecated
     *   Warning: all vendors but PostgreSQL don't have a small/big variant
     *   for floating point numbers, basically it's "real" or "float" every
     *   where but in PostgreSQL.
     */
    public function getSmallFloatType(): string
    {
        return 'real';
    }

    /**
     * Get float type.
     */
    public function getFloatType(): string
    {
        return 'real';
    }

    /**
     * Get float type.
     *
     * @deprecated
     *   Warning: all vendors but PostgreSQL don't have a small/big variant
     *   for floating point numbers, basically it's "real" or "float" every
     *   where but in PostgreSQL.
     */
    public function getBigFloatType(): string
    {
        return 'real';
    }

    /**
     * Get serial identity type.
     *
     * @deprecated
     *   "serial" is the ancestor of the standard SQL identity column that
     *   exists in PostgreSQL. Identity should always be used instead.
     */
    public function getSmallSerialType(): string
    {
        return $this->getSmallIdentityType();
    }

    /**
     * Get serial identity type.
     *
     * @deprecated
     *   "serial" is the ancestor of the standard SQL identity column that
     *   exists in PostgreSQL. Identity should always be used instead.
     */
    public function getSerialType(): string
    {
        return $this->getIdentityType();
    }

    /**
     * Get serial identity type.
     *
     * @deprecated
     *   "serial" is the ancestor of the standard SQL identity column that
     *   exists in PostgreSQL. Identity should always be used instead.
     */
    public function getBigSerialType(): string
    {
        return $this->getBigIdentityType();
    }

    /**
     * Get serial identity type.
     */
    public function getSmallIdentityType(): string
    {
        return $this->getSmallIntType();
    }

    /**
     * Get serial identity type.
     */
    public function getIdentityType(): string
    {
        return $this->getIntType();
    }

    /**
     * Get serial identity type.
     */
    public function getBigIdentityType(): string
    {
        return $this->getBigIntType();
    }

    /**
     * Validated and optimized JSON bynary type.
     *
     * Sadly, a lot of vendors do implement the JSON functions specification
     * of the standard, but don't have the standard JSON type. Only MySQL and
     * PostgreSQL do.
     */
    public function getJsonType(): string
    {
        return 'json';
    }

    /**
     * Get date interval type.
     *
     * Warning: you should be very careful when using the interval type
     * since that only PostgreSQL materializes it as a full blown type.
     * All other vendors have date interval related functions, but they
     * don't have a date interval type.
     */
    public function getDateIntervalType(): string
    {
        return 'interval';
    }

    /**
     * Get timestamp type.
     */
    public function getDateType(): string
    {
        return 'date';
    }

    /**
     * Get time type.
     */
    public function getTimeType(): string
    {
        return 'time';
    }

    /**
     * Get timestamp type.
     */
    public function getTimestampType(): string
    {
        return 'timestamp';
    }

    /**
     * Get character type.
     */
    public function getCharType(): string
    {
        return 'char';
    }

    /**
     * Get character varying type. 
     */
    public function getVarcharType(): string
    {
        return 'varchar';
    }

    /**
     * Get arbitrary text type.
     */
    public function getTextType(): string
    {
        return 'text';
    }

    /**
     * Get binary type.
     */
    public function getBinaryType(): string
    {
        return 'blob';
    }

    /**
     * Get integer cast type.
     */
    public function getIntCastType(): string
    {
        return $this->getBigIntType();
    }

    /**
     * Get text cast type.
     */
    public function getTextCastType(): string
    {
        return $this->getTextType();
    }

    /**
     * Get timestamp cast type.
     */
    public function getTimestampCastType(): string
    {
        return $this->getTimestampType();
    }
}
