<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Vendor;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testVersionCompareLessThan(): void
    {
        self::assertFalse(Vendor::versionCompare('5.6', '5.7.44', '<'));
        self::assertFalse(Vendor::versionCompare('5.7', '5.7.44', '<'));
        self::assertTrue(Vendor::versionCompare('8.0', '5.7.44', '<'));

        self::assertFalse(Vendor::versionCompare('5.6.0', '5.7.44', '<'));
        self::assertFalse(Vendor::versionCompare('5.7.0', '5.7.44', '<'));
        self::assertTrue(Vendor::versionCompare('8.0.0', '5.7.44', '<'));

        self::assertFalse(Vendor::versionCompare('5.7.43', '5.7.44', '<'));
        self::assertFalse(Vendor::versionCompare('5.7.44', '5.7.44', '<'));
        self::assertTrue(Vendor::versionCompare('5.7.45', '5.7.44', '<'));
    }

    public function testVersionCompareLessOrEqualThan(): void
    {
        self::assertFalse(Vendor::versionCompare('5.6', '5.7.44', '<='));
        self::assertTrue(Vendor::versionCompare('5.7', '5.7.44', '<='));
        self::assertTrue(Vendor::versionCompare('8.0', '5.7.44', '<='));

        self::assertFalse(Vendor::versionCompare('5.6.0', '5.7.44', '<='));
        self::assertFalse(Vendor::versionCompare('5.7.0', '5.7.44', '<='));
        self::assertTrue(Vendor::versionCompare('8.0.0', '5.7.44', '<='));

        self::assertFalse(Vendor::versionCompare('5.7.43', '5.7.44', '<='));
        self::assertTrue(Vendor::versionCompare('5.7.44', '5.7.44', '<='));
        self::assertTrue(Vendor::versionCompare('5.7.45', '5.7.44', '<='));
    }

    public function testVersionCompareEqualto(): void
    {
        self::assertFalse(Vendor::versionCompare('5.6', '5.7.44', '='));
        self::assertTrue(Vendor::versionCompare('5.7', '5.7.44', '='));
        self::assertFalse(Vendor::versionCompare('8.0', '5.7.44', '='));

        self::assertFalse(Vendor::versionCompare('5.6.0', '5.7.44', '='));
        self::assertFalse(Vendor::versionCompare('5.7.0', '5.7.44', '='));
        self::assertFalse(Vendor::versionCompare('8.0.0', '5.7.44', '='));

        self::assertFalse(Vendor::versionCompare('5.7.43', '5.7.44', '='));
        self::assertTrue(Vendor::versionCompare('5.7.44', '5.7.44', '='));
        self::assertFalse(Vendor::versionCompare('5.7.45', '5.7.44', '='));
    }

    public function testVersionCompareGreaterOrEqualThan(): void
    {
        self::assertTrue(Vendor::versionCompare('5.6', '5.7.44', '>='));
        self::assertTrue(Vendor::versionCompare('5.7', '5.7.44', '>='));
        self::assertFalse(Vendor::versionCompare('8.0', '5.7.44', '>='));

        self::assertTrue(Vendor::versionCompare('5.6.0', '5.7.44', '>='));
        self::assertTrue(Vendor::versionCompare('5.7.0', '5.7.44', '>='));
        self::assertFalse(Vendor::versionCompare('8.0.0', '5.7.44', '>='));

        self::assertTrue(Vendor::versionCompare('5.7.43', '5.7.44', '>='));
        self::assertTrue(Vendor::versionCompare('5.7.44', '5.7.44', '>='));
        self::assertFalse(Vendor::versionCompare('5.7.45', '5.7.44', '>='));
    }

    public function testVersionCompareGreaterThan(): void
    {
        self::assertTrue(Vendor::versionCompare('5.6', '5.7.44', '>'));
        self::assertFalse(Vendor::versionCompare('5.7', '5.7.44', '>'));
        self::assertFalse(Vendor::versionCompare('8.0', '5.7.44', '>'));

        self::assertTrue(Vendor::versionCompare('5.6.0', '5.7.44', '>'));
        self::assertTrue(Vendor::versionCompare('5.7.0', '5.7.44', '>'));
        self::assertFalse(Vendor::versionCompare('8.0.0', '5.7.44', '>'));

        self::assertTrue(Vendor::versionCompare('5.7.43', '5.7.44', '>'));
        self::assertFalse(Vendor::versionCompare('5.7.44', '5.7.44', '>'));
        self::assertFalse(Vendor::versionCompare('5.7.45', '5.7.44', '>'));
    }
}
