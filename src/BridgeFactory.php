<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\OCI8\Middleware\InitializeSession;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use MakinaCorpus\QueryBuilder\Bridge\Bridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoBridge;
use MakinaCorpus\QueryBuilder\Error\ConfigurationError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;

/**
 * Creates a database connection, wraps it in a bridge. Handles back the brige.
 *
 * This API is not meant to be a full database access layer, it is recommended
 * to let your framework configure the database then create the bridge instance
 * using its the framework connection.
 *
 * This will support only a subset of whatever is your primary driver supports
 * which is suitable for our unit tests and some additional use cases, but it
 * is not meant to be used in your own code. We strongly advice, for example,
 * that you use doctrine/dbal instead for managing the connection when you are
 * using the Symfony framework.
 *
 * Nevertheless, for some simple use case or when you want to have a standalone
 * connection, you may use this factory to create the connection for you.
 */
class BridgeFactory
{
    /**
     * Creates a new bridge from database URI.
     *
     * If driver is "any", PDO will be prefered, depending upon the choosen vendor.
     * If driver is "pdo", PDO will be used.
     * Any other will use doctrine/dbal per default.
     *
     * If you explicitely want a doctrine/dbal connection, use createDoctrine().
     * If you explicitely want a PDO connection, use createPdo().
     */
    public static function create(#[\SensitiveParameter] array|string|Dsn $uri): Bridge
    {
        $dsn = self::normalizeDsn($uri);

        return match ($dsn->getDriver()) {
            Dsn::DRIVER_ANY => self::createPdo($dsn),
            Dsn::DRIVER_PDO => self::createPdo($dsn),
            default => self::createDoctrine($dsn),
        };
    }

    /**
     * Creates connection using doctrine/dbal.
     *
     * Doctrine is the bridge that will give you the most vendor support and
     * configuration options.
     */
    public static function createDoctrine(#[\SensitiveParameter] array|string|Dsn $uri): DoctrineBridge
    {
        $dsn = self::normalizeDsn($uri);

        $driver = $dsn->getDriver();
        $vendor = $dsn->getVendor();

        // These are opiniated choices.
        // @see https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
        $doctrineDriver = match ($driver) {
            Dsn::DRIVER_ANY, Dsn::DRIVER_DOCTRINE => match ($vendor) {
                Vendor::MARIADB, Vendor::MYSQL => 'pdo_mysql',
                Vendor::ORACLE => 'oci8',
                Vendor::POSTGRESQL => 'pgsql',
                Vendor::SQLITE => 'sqlite3',
                Vendor::SQLSERVER => 'sqlsrv',
                default => $driver . '_' . $vendor,
            },
            Dsn::DRIVER_MYSQLI => 'mysqli',
            Dsn::DRIVER_SQLITE3 => 'sqlite3',
            default => match ($vendor) {
                Vendor::MARIADB, Vendor::MYSQL => 'pdo_mysql',
                Vendor::ORACLE => 'pdo_oci',
                Vendor::POSTGRESQL => 'pdo_pgsql',
                Vendor::SQLITE => 'pdo_sqlite',
                Vendor::SQLSERVER => 'pdo_sqlsrv',
                default => $driver . '_' . $vendor,
            },
        };

        $params = \array_filter([
            'dbname' => $dsn->getDatabase(),
            'driver' => $doctrineDriver,
            'driverOptions' => $dsn->getOptions(),
            'host' => $dsn->getHost(),
            'memory' => $dsn->inMemory(),
            'password' => $dsn->getPassword(),
            'path' => $dsn->getFilename(),
            'persistent' => self::valueBool('persistent', $dsn->getOption('memory')),
            'port' => $dsn->getPort(),
            'user' => $dsn->getUser(),
        ]);

        return new DoctrineBridge(
            DriverManager::getConnection(
                $params,
                self::doctrineConfiguration($params['driver']),
            ),
        );
    }

    /**
     * Creates connection using PDO.
     *
     * Whenever supported, you should choose PDO which will give you the best
     * performances (ext-pgsql aside).
     */
    public static function createPdo(#[\SensitiveParameter] array|string|Dsn $uri): PdoBridge
    {
        $dsn = self::normalizeDsn($uri);

        return new PdoBridge(
            match ($dsn->getVendor()) {
                Vendor::MYSQL => self::pdoConnectionMySQL($dsn),
                Vendor::POSTGRESQL => self::pdoConnectionPostgreSQL($dsn),
                Vendor::SQLITE => self::pdoConnectionSQLite($dsn),
                Vendor::SQLSERVER => self::pdoConnectionSQLServer($dsn),
                default => throw new UnsupportedFeatureError(\sprintf("Unsupported vendor '%s' in bridge factory.", $dsn->getVendor())),
            },
        );
    }

    /**
     * Normalize DSN.
     *
     * @internal
     *   Public for unit testing purpose.
     */
    public static function normalizeDsn(#[\SensitiveParameter] array|string|Dsn $uri): Dsn
    {
        if (\is_array($uri)) {
            if (empty($uri['driver'])) {
                throw new ConfigurationError("Option 'driver' is missing from parameters array.");
            }

            $options = \array_diff_key($uri, ['dbname' => 1, 'driver' => 1, 'host' => 1, 'memory' => 1, 'password' => 1, 'path' => 1, 'port' => 1, 'user' => 1]);

            return new Dsn(
                database: self::valueString('dbname', $uri['dbname'] ?? null),
                filename: self::valueString('path', $uri['path'] ?? null),
                host: self::valueString('host', $uri['host'] ?? null),
                memory: self::valueBool('memory', $uri['memory'] ?? false),
                password: self::valueString('password', $uri['password'] ?? null),
                port: self::valueInt('port', $uri['port'] ?? null),
                query: $options,
                user: self::valueString('user', $uri['user'] ?? null),
                vendor: self::valueString('driver', $uri['driver']),
            );
        }

        if (\is_string($uri)) {
            return Dsn::fromString($uri);
        }

        return $uri;
    }

    /**
     * Create MySQL PDO connection.
     */
    private static function pdoConnectionMySQL(#[\SensitiveParameter] Dsn $dsn): \PDO
    {
        $options = [];
        if ($value = $dsn->getHost()) {
            $options['host'] = $value;
        }
        if ($value = $dsn->getPort()) {
            $options['port'] = $value;
        }
        if ($value = $dsn->getDatabase()) {
            $options['dbname'] = $value;
        }
        $options += \array_diff_key($dsn->getOptions(), ['persistent' => 1]);

        $flags = [];
        $persistent = $dsn->getOption('peristent');
        if (null === $persistent || true === $persistent) {
            $flags[\PDO::ATTR_PERSISTENT] = true;
        }

        // @todo Make persistent flag user-configurable.
        return new \PDO('mysql:' . self::pdoConnectionString($options), $dsn->getUser(), $dsn->getPassword(), $flags);
    }

    /**
     * Create PostgreSQL PDO connection.
     */
    private static function pdoConnectionPostgreSQL(#[\SensitiveParameter] Dsn $dsn): \PDO
    {
        $options = [];
        if ($value = $dsn->getHost()) {
            $options['host'] = $value;
        }
        if ($value = $dsn->getPort()) {
            $options['port'] = $value;
        }
        if ($value = $dsn->getDatabase()) {
            $options['dbname'] = $value;
        }
        $options += \array_diff_key($dsn->getOptions(), ['persistent' => 1]);

        $flags = [];
        $persistent = $dsn->getOption('peristent');
        if (null === $persistent || true === $persistent) {
            $flags[\PDO::ATTR_PERSISTENT] = true;
        }

        // @todo Make persistent flag user-configurable.
        return new \PDO('pgsql:' . self::pdoConnectionString($options), $dsn->getUser(), $dsn->getPassword(), $flags);
    }

    /**
     * Create PostgreSQL PDO connection.
     */
    private static function pdoConnectionSQLServer(#[\SensitiveParameter] Dsn $dsn): \PDO
    {
        $options = [];
        if ($value = $dsn->getHost()) {
            $options['server'] = $value;
            if ($value = $dsn->getPort()) {
                $options['server'] .= ',' . $value;
            }
        }
        if ($value = $dsn->getDatabase()) {
            $options['Database'] = $value;
        }
        $options += \array_diff_key($dsn->getOptions(), ['persistent' => 1]);

        if (isset($options['MultipleActiveResultSets'])) {
            $options['MultipleActiveResultSets'] = $options['MultipleActiveResultSets'] ? 'true' : 'false';
        }
        if (isset($options['TrustServerCertificate'])) {
            $options['TrustServerCertificate'] = $options['TrustServerCertificate'] ? 'true' : 'false';
        }

        $flags = [];
        $persistent = $dsn->getOption('peristent');
        if (null === $persistent || true === $persistent) {
            // @todo This doesn't seem to be supported somehow.
            // $flags[\PDO::ATTR_PERSISTENT] = true;
        }

        // @todo Make persistent flag user-configurable.
        return new \PDO('sqlsrv:' . self::pdoConnectionString($options), $dsn->getUser(), $dsn->getPassword(), $flags);
    }

    /**
     * Create SQLite PDO connection.
     */
    private static function pdoConnectionSQLite(#[\SensitiveParameter] Dsn $dsn): \PDO
    {
        // Dsn::getFilename() may return ":memory:" which is supported by PDO.
        return new \PDO('sqlite:' . $dsn->getFilename());
    }

    /**
     * Compute own PDO connection string.
     */
    private static function pdoConnectionString(array $options): string
    {
        $values = [];
        foreach ($options as $name => $value) {
            $values[] = \sprintf("%s=%s", $name, $value);
        }
        return \implode(';', $values);
    }

    /**
     * Code copied from doctrine/dbal package.
     *
     * See the \Doctrine\DBAL\Tests\FunctionalTestCase class.
     */
    private static function doctrineConfiguration(string $driver): Configuration
    {
        $configuration = new Configuration();

        switch ($driver) {
            case 'pdo_oci':
            case 'oci8':
                $configuration->setMiddlewares([new InitializeSession()]);
                break;
            case 'pdo_sqlite':
            case 'sqlite3':
                $configuration->setMiddlewares([new EnableForeignKeys()]);
                break;
        }

        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }

    /**
     * Covnert user input as string.
     */
    private static function valueString(string $name, #[\SensitiveParameter] mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (\is_string($value)) {
            return $value;
        }
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        throw new ConfigurationError(\sprintf("Option '%s' must be a string, '%s' given.", $name, \get_debug_type($value)));
    }

    /**
     * Convert user input as integer.
     */
    private static function valueInt(string $name, #[\SensitiveParameter] mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && \ctype_digit($value)) {
            return (int) $value;
        }

        throw new ConfigurationError(\sprintf("Option '%s' must be a int, '%s' given.", $name, \get_debug_type($value)));
    }

    /**
     * Convert user input as integer.
     */
    private static function valueBool(string $name, #[\SensitiveParameter] mixed $value): ?bool
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (\is_bool($value)) {
            return $value;
        }
        if (\is_int($value)) {
            return (bool) $value;
        }
        if (\is_string($value)) {
            return !\in_array($value, ['false', 'f', 'no', 'n']);
        }

        throw new ConfigurationError(\sprintf("Option '%s' must be a bool, '%s' given.", $name, \get_debug_type($value)));
    }
}
