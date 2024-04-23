<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

/**
 * Various use case this DSN implements:
 *  - driver://user:pass@host:port/database?arg=value&arg=value...
 *  - driver:///path/to/file?arg=value&... (socket connection, or database file name).
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
    private readonly string $driver;
    private readonly string $vendor;

    public function __construct(
        /** Database vendor, eg. "mysql", "pgsql", ... */
        string $vendor,
        /** Host or local filename (unix socket, database file) */
        private readonly string $host,
        bool $isFile = false,
        /** Driver, eg. "pdo", "ext", ... */
        ?string $driver = null,
        private readonly ?string $database = null,
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
        $this->isFile = $isFile || '/' === $host[0];
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
            throw new \InvalidArgumentException('The database DSN must contain a scheme.');
        }

        if (false === ($params = \parse_url($dsn))) {
            throw new \InvalidArgumentException('The database DSN is invalid.');
        }

        if ($isFile) {
            if (empty($params['path'])) {
                throw new \InvalidArgumentException('The database DSN must contain a path when a targetting a local filename.');
            }
            $host = $params['path'];
            $database = $params['path'];
        } else {
            if (empty($params['host'])) {
                throw new \InvalidArgumentException('The database DSN must contain a host (use "default" by default).');
            }
            if (empty($params['path'])) {
                throw new \InvalidArgumentException('The database DSN must contain a database name.');
            }
            $host = $params['host'];
            $database = \trim($params['path'], '/');
        }

        $port = $params['port'] ?? null;
        $user = '' !== ($params['user'] ?? '') ? \rawurldecode($params['user']) : null;
        $password = '' !== ($params['pass'] ?? '') ? \rawurldecode($params['pass']) : null;

        $query = [];
        \parse_str($params['query'] ?? '', $query);

        return new self(
            database: $database,
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

    public function getHost(): string
    {
        return $this->host;
    }

    public function isFile(): bool
    {
        return $this->isFile;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getFilename(): ?string
    {
        return $this->isFile ? $this->host : null;
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
