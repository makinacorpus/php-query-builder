<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

/**
 * Represent internally any type, with length or precision and scale.
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

    /** Floating point numner. */
    case FLOAT;

    /** Big floating point numner. */
    case FLOAT_BIG;

    /** Small floating point numner. */
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

    /** JSON. */
    case JSON;

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

    public function isNumeric(): bool
    {
        return
            $this === self::DECIMAL ||
            $this === self::FLOAT || $this === self::FLOAT_BIG || $this === self::FLOAT_SMALL ||
            $this === self::INT || $this === self::INT_BIG || $this === self::INT_SMALL ||
            $this === self::IDENTITY || $this === self::IDENTITY_BIG || $this === self::IDENTITY_SMALL ||
            $this === self::SERIAL || $this === self::SERIAL_BIG || $this === self::SERIAL_SMALL
        ;
    }

    public function isText(): bool
    {
        return $this === self::CHAR || $this === self::TEXT || $this === self::VARCHAR;
    }
}
