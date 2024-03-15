<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\DateInputConverter;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

/**
 * This will fit with most RDBMS.
 *
 * @see https://www.postgresql.org/docs/13/datatype-datetime.html
 */
class DateOutputConverter implements OutputConverter
{
    #[\Override]
    public function supportedOutputTypes(): array
    {
        return [
            \DateTime::class,
            \DateTimeImmutable::class,
            \DateTimeInterface::class,
        ];
    }

    #[\Override]
    public function fromSql(string $type, int|float|string $value, ConverterContext $context): mixed
    {
        // I have no idea why this is still here. Probably an old bug.
        if (!$value = \trim((string) $value)) {
            return null;
        }

        // Matches all date and times, with or without timezone.
        if (\preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $value)) {
            $userTimeZone = new \DateTimeZone($context->getClientTimeZone());

            // SQL Server has a digit more for precision than the others.
            // let's just strip ip.
            if (\preg_match('/\.\d{7}$/', $value)) {
                $value = \substr($value, 0, -1);
            }

            // Attempt all possible outcomes.
            if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_DATETIME_USEC_TZ, $value)) {
                // Time zone is within the date, as an offset. Convert the
                // date to the user configured time zone, this conversion
                // is safe and time will not shift.
                $doConvert = true;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_DATETIME_USEC, $value, $userTimeZone)) {
                // We have no offset, change object timezone to be the user
                // configured one if different from PHP default one. This
                // will cause possible time shifts if client that inserted
                // this date did not have the same timezone configured.
                $doConvert = false;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_DATETIME_TZ, $value)) {
                // Once again, we have an offset. See upper.
                $doConvert = true;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_DATETIME, $value, $userTimeZone)) {
                // Once again, no offset. See upper.
                $doConvert = false;
            } else {
                throw new ValueConversionError(\sprintf("Given datetime '%s' could not be parsed.", $value));
            }

            if ($doConvert && $ret->getTimezone()->getName() !== $userTimeZone->getName()) {
                return $ret->setTimezone($userTimeZone);
            }
            return $ret;
        }

        // All other use case, simply date.
        if (\preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_DATE, $value)) {
                // Date only do not care about time zone.
            } else {
                throw new ValueConversionError(\sprintf("Given date '%s' could not be parsed.", $value));
            }
            return $ret;
        }

        // This is a fallback for "time" types, but it will set the current
        // date, and time offset will break your times.
        if (\preg_match('/^\d{2}:\d{2}:\d{2}/', $value)) {
            $userTimeZone = new \DateTimeZone($context->getClientTimeZone());

            // Attempt all possible outcomes.
            if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_TIME_USEC_TZ, $value)) {
                // Time zone is within the date, as an offset. Convert the
                // date to the user configured time zone, this conversion
                // is safe and time will not shift.
                $doConvert = true;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_TIME_USEC, $value, $userTimeZone)) {
                // We have no offset, change object timezone to be the user
                // configured one if different from PHP default one. This
                // will cause possible time shifts if client that inserted
                // this date did not have the same timezone configured.
                $doConvert = false;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_TIME_TZ, $value)) {
                // Once again, we have an offset. See upper.
                $doConvert = true;
            } else if ($ret = \DateTimeImmutable::createFromFormat(DateInputConverter::FORMAT_TIME, $value, $userTimeZone)) {
                // Once again, no offset. See upper.
                $doConvert = false;
            } else {
                throw new ValueConversionError(\sprintf("Given time '%s' could not be parsed.", $value));
            }

            if ($doConvert && $ret->getTimezone()->getName() !== $userTimeZone->getName()) {
                return $ret->setTimezone($userTimeZone);
            }
            return $ret;
        }

        throw new ValueConversionError("Value is not an SQL date.");
    }
}
