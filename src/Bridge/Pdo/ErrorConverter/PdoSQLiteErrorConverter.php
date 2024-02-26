<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter;

use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Error\Bridge\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Bridge\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Bridge\NotNullConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Bridge\ServerError;
use MakinaCorpus\QueryBuilder\Error\Bridge\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Bridge\TransactionDeadlockError;
use MakinaCorpus\QueryBuilder\Error\Bridge\UniqueConstraintViolationError;

class PdoSQLiteErrorConverter implements ErrorConverter
{
    public static function createErrorFromMessage(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        $message = $error->getMessage();
        $errorCode = $error->errorInfo[1] ?? $error->getCode();

        // Missing:
        //   - Connexion error
        //   - ForeignKeyConstraintViolationError
        //   - TransactionError
        //   - UniqueConstraintViolationError
        //   - Invalid identifier
        //   - Syntax error
        //   - Table exists

        if (\str_contains($message, 'database is locked') !== false) {
            return new TransactionDeadlockError($message, $errorCode, $error);
        }

        if (
            \str_contains($message, 'must be unique') !== false ||
            \str_contains($message, 'is not unique') !== false ||
            \str_contains($message, 'are not unique') !== false ||
            \str_contains($message, 'UNIQUE constraint failed') !== false
        ) {
            return new UniqueConstraintViolationError($message, $errorCode, $error);
        }

        if (
            \str_contains($message, 'may not be NULL') !== false ||
            \str_contains($message, 'NOT NULL constraint failed') !== false
        ) {
            return new NotNullConstraintViolationError($message, $errorCode, $error);
        }

        if (\str_contains($message, 'no such table:') !== false) {
            return new TableDoesNotExistError($message, $errorCode, $error);
        }

        if (\str_contains($message, 'no such column:') !== false) {
            return new ColumnDoesNotExistError($message, $errorCode, $error);
        }

        /*
        if (\str_contains($message, 'already exists') !== false) {
            return new TableExistsException($exception, $query);
        }
         */

        /*
        if (\str_contains($message, 'has no column named') !== false) {
            return new InvalidFieldNameException($exception, $query);
        }
         */

        if (\str_contains($message, 'ambiguous column name') !== false) {
            return new AmbiguousIdentifierError($message, $errorCode, $error);
        }

        /*
        if (\str_contains($message, 'syntax error') !== false) {
            return new SyntaxErrorException($exception, $query);
        }
         */

        /*
        if (\str_contains($message, 'attempt to write a readonly database') !== false) {
            return new ReadOnlyException($exception, $query);
        }
         */

        /*
        if (\str_contains($message, 'unable to open database file') !== false) {
            return new ConnectionException($exception, $query);
        }
         */

        return new ServerError($error->getMessage(), $error->getCode(), $error);
    }

    /**
     * {@inheritdoc}
     *
     * I have to admit, I was largely inspired by Doctrine DBAL for this one.
     * All credits to the Doctrine team, developers and contributors. You do
     * very impressive and qualitative work, I hope you will continue forever.
     * Many thanks to all contributors. If someday you come to France, give me
     * a call, an email, anything, and I'll pay you a drink, whoever you are.
     */
    public function convertError(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        if (!$error instanceof \PDOException) {
            return $error;
        }

        return self::createErrorFromMessage($error, $sql, $message);
    }
}
