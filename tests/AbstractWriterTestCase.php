<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use PHPUnit\Framework\TestCase;

abstract class AbstractWriterTestCase extends TestCase
{
    private static ?Writer $writer = null;

    private static function normalize($string)
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }

    protected static function setTestWriter(?Writer $writer): void
    {
        self::$writer = $writer;
    }

    protected static function createTestWriter(): Writer
    {
        return self::$writer ?? (self::$writer = new Writer(new StandardEscaper('#', 1)));
    }

    protected function assertSameSql(string|\Stringable|Expression $expected, string|\Stringable|Expression $actual, $message = null): void
    {
        if ($expected instanceof Expression) {
            $expected = static::createTestWriter()->prepare($expected)->toString();
        } else {
            $expected = (string) $expected;
        }

        if ($actual instanceof Expression) {
            $actual = static::createTestWriter()->prepare($actual)->toString();
        } else {
            $actual = (string) $actual;
        }

        if ($message) {
            self::assertSame(
                self::normalize((string) $expected),
                self::normalize((string) $actual),
                $message
            );
        }

        self::assertSame(
            self::normalize((string) $expected),
            self::normalize((string) $actual)
        );
    }
}
