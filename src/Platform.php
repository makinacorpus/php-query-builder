<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

/**
 * RDMBS identification.
 *
 * One could have used an enum here, but we want server identification
 * string to be arbitrary in various places in the code, to allow this
 * API to remain extensible.
 */
final class Platform
{
    const MARIADB = 'mariadb';
    const MYSQL = 'mysql';
    const ORACLE = 'oracle';
    const POSTGRESQL = 'postgresql';
    const SQLITE = 'sqlite';
    const SQLSERVER = 'sqlsrv';

    /**
     * Normalize version to an x.y.z semantic version string.
     */
    public static function versionNormalize(string $version): string
    {
        $matches = [];
        if (\preg_match('/(\d+)(\.\d+|)(\.\d+|).*/ims', $version, $matches)) {
            return $matches[1] . ($matches[2] ?: '.0') . ($matches[3] ?: '.0');
        }
        throw new \Exception(\sprintf("Database version '%s', is not in 'x.y.z' semantic format", $version));
    }
}
