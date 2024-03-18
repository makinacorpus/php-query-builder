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
     * Is given type any kind of numeric type.
     */
    public function isTypeNumeric(string|Type $type): bool
    {
        return Type::create($type)->isNumeric;
    }

    /**
     * Is given type integer.
     */
    public function isTypeIntCompatible(string|Type $type): bool
    {
        return Type::create($type)->isNumeric;
    }

    /**
     * Is given type varchar.
     */
    public function isTypeVarcharCompatible(string|Type $type): bool
    {
        return Type::create($type)->isText;
    }

    /**
     * Is given type date.
     */
    public function isTypeDateCompatible(string|Type $type): bool
    {
        $type = Type::create($type);

        return $type->internal === InternalType::DATE || $type->internal === InternalType::TIMESTAMP;
    }

    /**
     * Is given type timestamp.
     */
    public function isTypeTimestampCompatible(string|Type $type): bool
    {
        $type = Type::create($type);

        return $type->internal === InternalType::DATE || $type->internal === InternalType::TIMESTAMP;
    }

    /**
     * Is given type text.
     */
    public function isTypeTextCompatible(string|Type $type): bool
    {
        return Type::create($type)->isText;
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
