<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputTypeGuesser;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * This will fit with most RDBMS since that:
 *
 *   - MySQL will truncate date strings (so it ignores micro seconds),
 *   - MSSQL will handle micro seconds,
 *   - PostgreSQL handles much more.
 *
 * There a slight PostgreSQL only variant in reading output dates, which is
 * the attempt find time zone offset in SQL dates. This variation happens
 * when reading date from SQL only, so it cannot actually gives erroneous
 * values to the RDMBS and will not cause SQL syntax errors with servers that
 * don't support this.
 *
 * When inserting data, it considers that the RDBMS connection has been set up
 * with the same user configured time zone than we have in memory, so that the
 * database server will proceed to conversions by itself if necessary. This is
 * actually the case with PostgreSQL. In that regard, we only convert time
 * zones when the input \DateTimeInterface has not the same time zone as the
 * user configured time zone in order to give the server the correct date.
 *
 * @see https://www.postgresql.org/docs/13/datatype-datetime.html
 */
class DateInputConverter implements InputConverter, InputTypeGuesser
{
    public const FORMAT_DATE = 'Y-m-d';
    public const FORMAT_DATETIME = 'Y-m-d H:i:s';
    public const FORMAT_DATETIME_TZ = 'Y-m-d H:i:sP';
    public const FORMAT_DATETIME_USEC = 'Y-m-d H:i:s.u';
    public const FORMAT_DATETIME_USEC_TZ = 'Y-m-d H:i:s.uP';
    public const FORMAT_TIME = 'H:i:s';
    public const FORMAT_TIME_TZ = 'H:i:sP';
    public const FORMAT_TIME_USEC = 'H:i:s.u';
    public const FORMAT_TIME_USEC_TZ = 'H:i:s.uP';

    #[\Override]
    public function supportedInputTypes(): array
    {
        return [Type::date(), Type::time(), Type::timestamp()];
    }

    #[\Override]
    public function guessInputType(mixed $value): null|string|Type
    {
        if ($value instanceof \DateTimeInterface) {
            return Type::timestamp(true);
        }
        return null;
    }

    #[\Override]
    public function toSql(Type $type, mixed $value, ConverterContext $context): null|int|float|string
    {
        if (!$value instanceof \DateTimeInterface) {
            throw UnexpectedInputValueTypeError::create(\DateTimeInterface::class, $value);
        }

        switch ($type->internal) {

            case InternalType::DATE:
                return $value->format(self::FORMAT_DATE);

            case InternalType::TIME:
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());
                // If user given date time is not using the client timezone
                // enforce conversion on the PHP side, since the SQL backend
                // does not care about the time zone at this point and will
                // not accept it.
                if ($value->getTimezone()->getName() !== $userTimeZone->getName()) {
                    if (!$value instanceof \DateTimeImmutable) {
                        // Avoid side-effect in user data.
                        $value = clone $value;
                    }
                    $value = $value->setTimezone($userTimeZone);
                }
                return $value->format(self::FORMAT_TIME_USEC);

            case InternalType::TIMESTAMP:
            default:
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());
                // If user given date time is not using the client timezone
                // enforce conversion on the PHP side, since the SQL backend
                // does not care about the time zone at this point and will
                // not accept it.
                if ($value->getTimezone()->getName() !== $userTimeZone->getName()) {
                    if (!$value instanceof \DateTimeImmutable) {
                        // Avoid side-effect in user data.
                        $value = clone $value;
                    }
                    $value = $value->setTimezone($userTimeZone);
                }
                return $value->format(self::FORMAT_DATETIME_USEC);
        }
    }
}
