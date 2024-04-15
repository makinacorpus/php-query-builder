<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

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
     * Is given type compatible with the other one.
     */
    public function isCompatible(Type $type, Type $reference): bool
    {
        return ($type->isText() && $reference->isText()) || ($type->isNumeric() && $reference->isNumeric()) || ($type->isDate() && $reference->isDate());
    }

    public function getSqlTypeString(): string
    {
        throw new QueryBuilderError("implement me.");
    }

    public function getSqlTypeName(Type $type): string
    {
        return match ($type->internal) {
            InternalType::BINARY => $this->getBinaryType(),
            InternalType::BOOL => $this->getBoolType(),
            InternalType::CHAR => $this->getCharType(),
            InternalType::DATE => $this->getDateType(),
            InternalType::DATE_INTERVAL => $this->getDateIntervalType(),
            InternalType::DECIMAL => $this->getDecimalType(),
            InternalType::FLOAT => $this->getFloatType(),
            InternalType::FLOAT_BIG => $this->getBigFloatType(),
            InternalType::FLOAT_SMALL => $this->getSmallFloatType(),
            InternalType::IDENTITY => $this->getIdentityType(),
            InternalType::IDENTITY_BIG => $this->getBigIdentityType(),
            InternalType::IDENTITY_SMALL => $this->getSmallIdentityType(),
            InternalType::INT => $this->getIntType(),
            InternalType::INT_BIG => $this->getBigIntType(),
            InternalType::INT_SMALL => $this->getSmallIntType(),
            InternalType::INT_TINY => $this->getTinyIntType(),
            InternalType::JSON => $this->getJsonType(),
            InternalType::NULL => 'null',
            InternalType::SERIAL => $this->getSerialType(),
            InternalType::SERIAL_BIG => $this->getBigSerialType(),
            InternalType::SERIAL_SMALL => $this->getSmallSerialType(),
            InternalType::TEXT => $this->getTextType(),
            InternalType::TIME => $this->getTimeType(),
            InternalType::TIMESTAMP => $this->getTimestampType(),
            InternalType::VARCHAR => $this->getVarcharType(),
            InternalType::UUID => $this->getUuidType(),
            InternalType::UNKNOWN => $type->name ?? throw new QueryBuilderError("Unhandled types must have a type name."),
        };
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
    public function getTinyIntType(): string
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

    /**
     * Get timestamp cast type.
     */
    public function getUuidType(): string
    {
        return 'uuid';
    }
}
