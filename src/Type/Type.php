<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

/**
 * Represent internally any type, with length or precision and scale.
 */
class Type
{
    public readonly ?string $name;
    private ?bool $isDate = null;
    private ?bool $isIdentity = null;
    private ?bool $isNumeric = null;
    private ?bool $isSerial = null;
    private ?bool $isText = null;

    public function __construct(
        public InternalType $internal,
        ?string $name = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $withTimeZone = false,
        public bool $array = false,
        public bool $unsigned = false,
    ) {
        if (!$name && $internal === InternalType::UNKNOWN) {
            throw new QueryBuilderError("Unhandled internal types must have a type name.");
        }
        $this->name = $name ? \strtolower(\trim($name)) : null;
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
     * Integer (small).
     */
    public static function intTiny(): self
    {
        return new self(internal: InternalType::INT_TINY);
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
     * Character varying text, variable length with maximum size limit.
     */
    public static function uuid(): self
    {
        return new self(internal: InternalType::UUID);
    }

    /**
     * Convert given user input type to vendor type.
     */
    public static function create(string|self $userType, bool $raw = false): self
    {
        if ($userType instanceof self) {
            return $userType;
        }

        $unsigned = $array = $withTimeZone = false;
        $length = $precision = $scale = null;

        // Catch precision and scale or length.
        $matches = [];
        if (\preg_match(
            <<<REGEX
            @
            (unsigned\s+|)                          # unsigned
            ([^\(\[]*)                              # type name
            (\s*\(\s*(\d+)(|\s*,\s*(\d+))\s*\)|)    # (length) or (precision,scale)
            (\s*\[\]|)                              # array
            @imx
            REGEX,
            $userType,
            $matches,
        )) {
            if ($matches[1]) {
                $unsigned = true;
            }
            if ($matches[7]) {
                $array = true;
            }
            if ($matches[4]) {
                if ($matches[6]) {
                    $precision = (int) $matches[4];
                    $scale = (int) $matches[6];
                } else {
                    $length = (int) $matches[4];
                }
            }
            $userType = $matches[2];

            if (\preg_match('@(.*)\s+with(out|)\s+time\s+zone@imx', $userType, $matches)) {
                $userType = $matches[1];
                $withTimeZone = !$matches[2];
            }
            $userType = \trim($userType);
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

        if (!$withTimeZone && ($userType === 'datetimez' || $userType === 'timestampz' || $userType === 'timez')) {
            $withTimeZone = true;
        }

        $internalType = InternalType::fromTypeName($userType);

        return new Type(
            array: $array,
            internal: $internalType,
            length: $length,
            name: InternalType::UNKNOWN === $internalType ? $userType : null,
            precision: $precision,
            scale: $scale,
            unsigned: $unsigned,
            withTimeZone: $withTimeZone,
        );
    }

    /**
     * Get arbitrary key based upon type name.
     *
     * @internal
     *   For converter caching only.
     */
    public function getArbitraryName(): string
    {
        return match ($this->internal) {
            InternalType::BINARY => 'binary',
            InternalType::BOOL => 'bool',
            InternalType::CHAR => 'char',
            InternalType::DATE => 'date',
            InternalType::DATE_INTERVAL => 'date_interval',
            InternalType::DECIMAL => 'decimal',
            InternalType::FLOAT => 'float',
            InternalType::FLOAT_BIG => 'float_big',
            InternalType::FLOAT_SMALL => 'float_small',
            InternalType::IDENTITY => 'id',
            InternalType::IDENTITY_BIG => 'id_big',
            InternalType::IDENTITY_SMALL => 'id_small',
            InternalType::INT => 'int',
            InternalType::INT_BIG => 'int_big',
            InternalType::INT_SMALL => 'int_small',
            InternalType::INT_TINY => 'int_tiny',
            InternalType::JSON => 'json',
            InternalType::NULL => 'null',
            InternalType::SERIAL => 'serial',
            InternalType::SERIAL_BIG => 'serial_big',
            InternalType::SERIAL_SMALL => 'serial_small',
            InternalType::TEXT => 'text',
            InternalType::TIME => 'time',
            InternalType::TIMESTAMP => 'timestamp',
            InternalType::VARCHAR => 'varchar',
            InternalType::UNKNOWN => $this->name ?? new QueryBuilderError("Unhandled types must have a type name."),
            InternalType::UUID => 'uuid',
        };
    }

    public function isDate(): bool
    {
        return $this->isDate ??= $this->internal->isDate();
    }

    public function isIdentity(): bool
    {
        return $this->isIdentity ??= $this->internal->isIdentity();
    }

    public function isNumeric(): bool
    {
        return $this->isNumeric ??= $this->internal->isNumeric();
    }

    public function isSerial(): bool
    {
        return $this->isSerial ??= $this->internal->isSerial();
    }

    public function isText(): bool
    {
        return $this->isText ??= $this->internal->isText();
    }

    public function cleanUp(): self
    {
        return new self(
            array: $this->array,
            internal: $this->internal,
            length: $this->internal->lengthIsRelevant() ? $this->length : null,
            name: $this->internal === InternalType::UNKNOWN ? $this->name : null,
            precision: $this->internal->precisionIsRelevant() ? $this->precision : null,
            scale: $this->internal->scaleIsRelevant() ? $this->scale : null,
            unsigned: $this->unsigned,
            withTimeZone: $this->withTimeZone,
        );
    }

    public function toArray(): self
    {
        $ret = clone $this;
        $ret->array = true;

        return $ret;
    }

    public function toNonArray(): self
    {
        $ret = clone $this;
        $ret->array = false;

        return $ret;
    }

    /**
     * Debug string representation, not suitable for production use.
     */
    #[\Override]
    public function __toString(): string
    {
        $prefix = $this->unsigned ? 'unsigned ' : '';

        $suffix = '';
        if ($this->length) {
            $suffix = '(' . $this->length . ')';
        } else if ($this->precision && $this->scale) {
            $suffix = '(' . $this->precision . ',' . $this->scale .  ')';
        }
        if ($this->withTimeZone) {
            $suffix .= ' with time zone';
        }
        if ($this->array) {
            $suffix .= '[]';
        }

        return $prefix . $this->getArbitraryName() . $suffix;
    }
}
