<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;
use MakinaCorpus\QueryBuilder\Error\Server\UnableToConnectError;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;

/**
 * Represent an active connected session.
 *
 * Mostly gives you some link state information and executes queries.
 *
 * This is part of bridges, avoiding the hard dependency.
 */
interface DatabaseSession extends QueryBuilder
{
    /**
     * Get current database name.
     */
    public function getCurrentDatabase(): string;

    /**
     * Get default schema name.
     */
    public function getDefaultSchema(): string;

    /**
     * Get database vendor name.
     *
     * Returns 'unknown' when unknown.
     */
    public function getVendorName(): string;

    /**
     * Get database vendor version.
     *
     * Returns '0.0.0' when unknown.
     */
    public function getVendorVersion(): string;

    /**
     * Compare vendor name against given name.
     *
     * Allow to give a version constraint as well.
     *
     * @param string|string[] $name
     *   One of the Vendor::* constant, eg. 'mysql', 'postgresql', ...
     *   Bare string are allowed and it will attempt to overcome typo errors
     *   but using constants is safer.
     * @param null|string $version
     *   Version to compare against, must be a valid semantic version string.
     * @param string $operator
     *   Operator for version comparison, can be one of: '>=', '>', '=', '<', '<='.
     *   Any invalid operator will raise an exception.
     */
    public function vendorIs(string|array $name, ?string $version = null, string $operator = '>='): bool;

    /**
     * Compare vendor version against given one.
     *
     * @param string $version
     *   Version to compare against, must be a valid semantic version string.
     * @param string $operator
     *   Operator for version comparison, can be one of: '>=', '>', '=', '<', '<='.
     *   Any invalid operator will raise an exception.
     */
    public function vendorVersionIs(string $version, string $operator = '>='): bool;

    /**
     * Execute query and return result.
     */
    public function executeQuery(string|Expression $expression = null, mixed $arguments = null): Result;

    /**
     * Execute query and return affected row count if possible.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    public function executeStatement(string|Expression $expression = null, mixed $arguments = null): ?int;

    /**
     * Creates a new transaction.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started.
     * @param bool $allowPending = true
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised.
     *
     * @throws TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting.
     *
     * @return Transaction
     */
    public function createTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction;

    /**
     * Alias of createTransaction() but it will force it to start
     */
    public function beginTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction;

    /**
     * Get schema manager.
     *
     * @experimental
     */
    public function getSchemaManager(): SchemaManager;

    /**
     * Close connection.
     *
     * If the bridge cannot close the connection, it will simply do nothing.
     */
    public function close(): void;

    /**
     * Connect the connection.
     *
     * If connection is not closed, does nothing.
     *
     * If brige does not support opening the connection, this will raise
     * an exception.
     *
     * @throws UnableToConnectError
     */
    public function connect(): void;
}
