<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Version\Version;

/**
 * RDMBS identification.
 *
 * One could have used an enum here, but we want server identification
 * string to be arbitrary in various places in the code, to allow this
 * API to remain extensible.
 */
/* final */ class Vendor
{
    public const MARIADB = 'mariadb';
    public const MYSQL = 'mysql';
    public const ORACLE = 'oracle';
    public const POSTGRESQL = 'pgsql';
    public const SQLITE = 'sqlite';
    public const SQLSERVER = 'sqlsrv';
    public const UNKNOWN = 'unknown';

    /**
     * Normalize version to an x.y.z semantic version string.
     */
    public static function versionNormalize(string $version): string
    {
        $matches = [];
        if (\preg_match('/(\d+)(\.\d+|)(\.\d+|).*/ims', $version, $matches)) {
            return $matches[1] . ($matches[2] ?: '.0') . ($matches[3] ?: '.0');
        }
        throw new QueryBuilderError(\sprintf("Version '%s', is not in 'x.y.z' semantic format", $version));
    }

    /**
     * Compare a user given version against another one.
     */
    public static function versionCompare(string $userGiven, string $serverVersion, string $operator): bool
    {
        return (new Version($userGiven))->compare($serverVersion, $operator);
    }

    /**
     * Attempt vendor name normalization against known constants.
     */
    public static function vendorNameNormalize(string $name): string
    {
        $reduced = \preg_replace('/[^a-z]+/', '', \strtolower($name));

        return match ($reduced) {
            'maria', 'mariadb' => self::MARIADB,
            'mysql', 'mysqli', 'my' => self::MYSQL,
            'oracle' => self::ORACLE,
            'postgresql', 'pgsql', 'postgre', 'postgres', 'pg' => self::POSTGRESQL,
            'sqlsrv', 'sqlserver', 'mssql', => self::SQLSERVER,
            'sqlite' => self::SQLITE,
            default => $reduced,
        };
    }
}
