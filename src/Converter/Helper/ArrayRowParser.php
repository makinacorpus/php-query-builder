<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\Helper;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\ValueConversionError;

/**
 * In standard SQL, array are types, you can't have an array with different
 * types within, in opposition to rows, which can have different values.
 *
 * This is very sensitive code, any typo will breaks tons of stuff, please
 * always run unit tests, everytime you modify anything in here, really.
 *
 * And yes, sadly, it is very slow. This code could heavily benefit from being
 * pluggable and use some FFI plugged library developed using any other faster
 * language/techno.
 */
final class ArrayRowParser
{
    /**
     * Is the given raw SQL value an array.
     */
    public static function isArray(string $string): bool
    {
        return ($length = \strlen($string)) >= 2 && '{' === $string[0] && '}' === $string[$length - 1];
    }

    /**
     * Parse a pgsql array return string.
     *
     * @return string[]
     *   Raw SQL values.
     */
    public static function parseArray(string $string): array
    {
        $string = \trim($string);
        $length = \strlen($string);

        if (0 === $length) { // Empty string
            return [];
        }
        if ($length < 2) {
            throw new QueryBuilderError("malformed input: string length must be 0 or at least 2");
        }
        if ('{' !== $string[0] || '}' !== $string[$length - 1]) {
            throw new QueryBuilderError("malformed input: array must be enclosed using {}");
        }

        return self::parseRecursion($string, 1, $length, '}')[0];
    }

    /**
     * Parse a row.
     */
    public static function parseRow(string $string): array
    {
        $length = \strlen($string);

        if (0 === $length) { // Empty string
            return [];
        }
        if ($length < 2) {
            throw new QueryBuilderError("malformed input: string length must be 0 or at least 2");
        }
        if ('(' !== $string[0] || ')' !== $string[$length - 1]) {
            throw new QueryBuilderError("malformed input: row must be enclosed using ()");
        }

        return self::parseRecursion($string, 1, $length, ')')[0];
    }

    /**
     * Write a row.
     *
     * Each value can have a different type, this is supposed to be called when
     * the query builder builds the query, it's the value formatter job to know
     * what to do which each value, which could be itself an Expression object,
     * hence the need for a serializer callback here.
     */
    public static function writeRow(array $row, callable $serializeItem): string
    {
        return '{'.\implode(',', \array_map($serializeItem, $row)).'}';
    }

    /**
     * Write array.
     *
     * Every value is supposed to have the same type, but this is supposed to
     * be at the discretion of who's asking for writing this array, usually the
     * SQL formatter, hence the need for a serializer callback here.
     */
    public static function writeArray(array $data, callable $serializeItem): string
    {
        $values = [];

        foreach ($data as $value) {
            if (\is_array($value)) {
                $values[] = self::writeArray($value, $serializeItem);
            } else {
                $values[] = \call_user_func($serializeItem, $value);
            }
        }

        return '{'.\implode(',', $values).'}';
    }

    /**
     * From a quoted string, find the end of it and return it.
     */
    private static function findUnquotedStringEnd(string $string, int $start, int $length, string $endChar): int
    {
        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];
            if (',' === $char || $endChar === $char) {
                return $i - 1;
            }
        }
        throw new ValueConversionError("malformed input: unterminated unquoted string starting at ".$start);
    }

    /**
     * From a unquioted string, find the end of it and return it.
     */
    private static function findQuotedStringEnd(string $string, int $start, int $length): int
    {
        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];
            if ('\\' === $char) {
                if ($i === $length) {
                    throw new QueryBuilderError(\sprintf("misplaced \\ escape char at end of string"));
                }
                $i++; // Skip escaped char
            } else if ('"' === $char) {
                return $i;
            }
        }
        throw new ValueConversionError("malformed input: unterminated quoted string starting at ".$start);
    }

    /**
     * Unescape escaped user string from SQL.
     */
    private static function unescapeString(string $string): string
    {
        return \str_replace('\\\\', '\\', \str_replace('\\"', '"', $string));
    }

    /**
     * Parse any coma-separated values string from SQL.
     */
    private static function parseRecursion(string $string, int $start, int $length, string $endChar): array
    {
        $ret = [];

        for ($i = $start; $i < $length; ++$i) {
            $char = $string[$i];
            if (',' === $char) {
                // Next string
            } else if ('(' === $char) { // Row.
                list($child, $stop) = self::parseRecursion($string, $i + 1, $length, ')');
                $ret[] = $child;
                $i = $stop;
            } else if ('{' === $char) { // Array.
                list($child, $stop) = self::parseRecursion($string, $i + 1, $length, '}');
                $ret[] = $child;
                $i = $stop;
            } else if ($endChar === $char) { // End of recursion.
                return [$ret, $i + 1];
            } else if ('}' === $char) {
                throw new QueryBuilderError(\sprintf("malformed input: unexpected end of array '}' at position %d", $i));
            } else if (')' === $char) {
                throw new QueryBuilderError(\sprintf("malformed input: unexpected end of row ')' at position %d", $i));
            } else if (',' === $char) {
                throw new QueryBuilderError(\sprintf("malformed input: unexpected separator ',' at position %d", $i));
            } else {
                if ('"' === $char) {
                    $i++; // Skip start quote
                    $stop = self::findQuotedStringEnd($string, $i, $length);
                    $ret[] = self::unescapeString(\substr($string, $i, $stop - $i));
                } else {
                    $stop = self::findUnquotedStringEnd($string, $i, $length, $endChar);
                    $ret[] = \substr($string, $i, $stop - $i + 1);
                }
                $i = $stop;
            }
        }

        return [$ret, $length];
    }
}
