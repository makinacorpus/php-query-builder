<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\ConfigurationError;

/**
 * Various use case this DSN implements:
 *  - driver://user:pass@host:port/database?arg=value&arg=value...
 *  - driver:///path/to/file?arg=value&... (socket connection, or database file name).
 *  - driver:///:memory:?arg=value&... (for example sqlite in memory)
 */
class Dsn
{
    public const DRIVER_ANY = 'any';
    public const DRIVER_DOCTRINE = 'doctrine';
    public const DRIVER_EXTPGSQL = 'ext-pgsql';
    public const DRIVER_MYSQLI = 'mysqli';
    public const DRIVER_PDO = 'pdo';
    public const DRIVER_SQLITE3 = 'sqlite3';

    private string $scheme;
    private readonly bool $isFile;
    private readonly bool $memory;
    private readonly string $driver;
    private readonly string $vendor;
    private readonly ?string $host;
    private readonly ?string $filename;
    private readonly ?string $database;

    public function __construct(
        /** Database vendor, eg. "mysql", "pgsql", ... */
        string $vendor,
        /** Database hostname, mutually exclusive with filename and memory. */
        ?string $host = null,
        /** Database filename or unix socket, mutually exclusive with host and memory. */
        ?string $filename = null,
        bool $isFile = false,
        /** Database in memory, mutually exclusive with host and filename. */
        bool $memory = false,
        /** Driver, eg. "pdo", "ext", ... */
        ?string $driver = null,
        ?string $database = null,
        private readonly ?string $user = null,
        #[\SensitiveParameter]
        private readonly ?string $password = null,
        private readonly ?int $port = null,
        private readonly array $query = [],
    ) {
        // Deal with some exceptions.
        switch ($vendor) {
            case 'ext-pgsql':
                $this->driver = self::DRIVER_EXTPGSQL;
                $this->vendor = Vendor::POSTGRESQL;
                $this->scheme = $vendor;
                break;
            case 'mysqli':
                $this->driver = self::DRIVER_MYSQLI;
                $this->vendor = Vendor::MYSQL;
                $this->scheme = $vendor;
                break;
            case 'sqlite3':
                $this->driver = self::DRIVER_SQLITE3;
                $this->vendor = Vendor::SQLITE;
                $this->scheme = $vendor;
                $isFile = true;
                break;
            default:
                $matches = [];
                if (\preg_match('@^([^-_+]+)[-_+](.+)$@i', $vendor, $matches)) {
                    $this->driver = $matches[1];
                    $this->vendor = Vendor::vendorNameNormalize($matches[2]);
                    $this->scheme = $vendor;
                } else {
                    $this->driver = $driver ?? self::DRIVER_ANY;
                    $this->vendor = $vendor;
                    $this->scheme = $this->driver . '-' . $vendor;
                }
                break;
        }

        if (!$host && !$filename && !$memory) {
            throw new ConfigurationError("Either one of \$host, \$filename or \$memory parameter must be provided.");
        }

        if ($memory) {
            if ($filename && $filename !== ':memory:') {
                throw new ConfigurationError(\sprintf("When \$memory is true, filename must be null or equal to ':memory:', found '%s'.", $filename));
            }
            $filename = ':memory:';
        } else if (':memory:' === $filename) {
            $memory = true;
        }

        $isFile = !$memory && ($isFile || $filename || '/' === $host[0] || Vendor::SQLITE === $this->vendor);

        // Fix an edge case where sqlite is not detected.
        if ($isFile) {
            if (!$filename) {
                $filename = $database ?? $host;
                $database = $host = null;
            }
            $isFile = true;
        } else {
            $database = $database ? \trim($database, '/') : null;
        }

        $this->database = $database;
        $this->filename = $filename;
        $this->host = $host;
        $this->isFile = $isFile;
        $this->memory = $memory;
    }

    public static function fromString(#[\SensitiveParameter] string $dsn): self
    {
        $scheme = null;
        $isFile = false;

        // parse_url() disallow some schemes with special characters we need
        // such as using an underscore in it. Let's split the scheme first then
        // parse_url() the rest.
        // It also disallows triple slash for other than file:// scheme, but we
        // do allow them, for example sqlite:///foo.db, or for connections via
        // a UNIX socket.
        $matches = [];
        if (\preg_match('@^([^:/]+)://(.*)$@i', $dsn, $matches)) {
            $scheme = $matches[1];
            if ($isFile = ('/' === $matches[2][0])) {
                $dsn = 'file://' . $matches[2];
            } else {
                $dsn = 'mock://' . $matches[2];
            }
        } else {
            throw new ConfigurationError('The database DSN must contain a scheme.');
        }

        if (false === ($params = \parse_url($dsn))) {
            throw new ConfigurationError('The database DSN is invalid.');
        }

        $database = $host = $filename = null;

        if ($isFile) {
            if (empty($params['path'])) {
                throw new ConfigurationError('The database DSN must contain a path when a targetting a local filename.');
            }
            $filename = $params['path'];
        } else {
            if (empty($params['host'])) {
                throw new ConfigurationError('The database DSN must contain a host (use "default" by default).');
            }
            if (isset($params['path'])) {
                // If path was absolute and is a filename then it should take
                // the form of "//some/path". If URL path is a database name,
                // then we must remove the leading '/' in all cases. In the
                // other hand, if database is a filename that wasn't prior
                // detected then we have '//' leading here. In both cases we
                // need to strip exactly one '/'.
                $database = '/' === $params['path'][0] ? \substr($params['path'], 1) : $params['path'];
            }
            $host = $params['host'];
        }

        // parse_url() few edge cases.
        if (':memory' === $host) {
            $filename = ':memory:';
            $host = null;
        } else if ('/:memory:' === $filename) {
            $filename = ':memory:';
        }

        $port = $params['port'] ?? null;
        $user = '' !== ($params['user'] ?? '') ? \rawurldecode($params['user']) : null;
        $password = '' !== ($params['pass'] ?? '') ? \rawurldecode($params['pass']) : null;

        $query = [];
        \parse_str($params['query'] ?? '', $query);

        return new self(
            database: $database,
            filename: $filename,
            host: $host,
            isFile: $isFile,
            password: $password,
            port: $port,
            query: $query,
            user: $user,
            vendor: $scheme,
        );
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Get host name, if none provided and database is a file, return filename.
     */
    public function getHost(): string
    {
        return $this->host ?? $this->filename;
    }

    /**
     * Is the database a file.
     */
    public function isFile(): bool
    {
        return $this->isFile;
    }

    /**
     * Is a database in memory.
     */
    public function inMemory(): bool
    {
        return $this->memory;
    }

    /**
     * Get database name, can be null.
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Get filename if detected as such.
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(?int $default = null): ?int
    {
        return $this->port ?? $default;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getOptions(): array
    {
        return $this->query;
    }

    public function toUrl(array $excludeParams = []): string
    {
        $database = $this->database ?? '';

        $queryString = '';
        if ($this->query && $values = \array_diff_key($this->query, \array_flip($excludeParams))) {
            $queryString = (\str_contains($database, '?') ? '&' : '?') . \http_build_query($values);
        }

        $authString = $this->user ? (\rawurlencode($this->user) . ($this->password ? ':' .  \rawurlencode($this->password) : '') . '@') : '';

        return $this->scheme . '://' . $authString . $this->host . ($this->port ? ':' . $this->port : '') . '/' . \ltrim($database, '/') . $queryString;
    }
}
