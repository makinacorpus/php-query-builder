<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter;

use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Error\Server\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Server\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\NotNullConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\ServerError;
use MakinaCorpus\QueryBuilder\Error\Server\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\TransactionDeadlockError;
use MakinaCorpus\QueryBuilder\Error\Server\UniqueConstraintViolationError;

class PdoSQLiteErrorConverter implements ErrorConverter
{
    public static function createErrorFromMessage(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        $serverMessage = $error->getMessage();
        $errorCode = $error->errorInfo[1] ?? $error->getCode();

        $message ??= $serverMessage;
        if ($sql) {
            $message .= "\nQuery was: " . $sql;
        }

        // Missing:
        //   - Connexion error
        //   - ForeignKeyConstraintViolationError
        //   - TransactionError
        //   - UniqueConstraintViolationError
        //   - Invalid identifier
        //   - Syntax error
        //   - Table exists

        if (\str_contains($serverMessage, 'database is locked') !== false) {
            return new TransactionDeadlockError($message, $errorCode, $error);
        }

        if (
            \str_contains($serverMessage, 'must be unique') !== false ||
            \str_contains($serverMessage, 'is not unique') !== false ||
            \str_contains($serverMessage, 'are not unique') !== false ||
            \str_contains($serverMessage, 'UNIQUE constraint failed') !== false
        ) {
            return new UniqueConstraintViolationError($message, $errorCode, $error);
        }

        if (
            \str_contains($serverMessage, 'may not be NULL') !== false ||
            \str_contains($serverMessage, 'NOT NULL constraint failed') !== false
        ) {
            return new NotNullConstraintViolationError($message, $errorCode, $error);
        }

        if (\str_contains($serverMessage, 'no such table:') !== false) {
            return new TableDoesNotExistError($message, $errorCode, $error);
        }

        if (\str_contains($serverMessage, 'no such column:') !== false) {
            return new ColumnDoesNotExistError($message, $errorCode, $error);
        }

        /*
        if (\str_contains($serverMessage, 'already exists') !== false) {
            return new TableExistsException($exception, $query);
        }
         */

        /*
        if (\str_contains($message, 'has no column named') !== false) {
            return new InvalidFieldNameException($exception, $query);
        }
         */

        if (\str_contains($serverMessage, 'ambiguous column name') !== false) {
            return new AmbiguousIdentifierError($message, $errorCode, $error);
        }

        /*
        if (\str_contains($serverMessage, 'syntax error') !== false) {
            return new SyntaxErrorException($exception, $query);
        }
         */

        /*
        if (\str_contains($message, 'attempt to write a readonly database') !== false) {
            return new ReadOnlyException($exception, $query);
        }
         */

        /*
        if (\str_contains($serverMessage, 'unable to open database file') !== false) {
            return new ConnectionException($exception, $query);
        }
         */

        return new ServerError($error->getMessage(), $error->getCode(), $error);
    }

    /**
     * I have to admit, I was largely inspired by Doctrine DBAL for this one.
     * All credits to the Doctrine team, developers and contributors. You do
     * very impressive and qualitative work, I hope you will continue forever.
     * Many thanks to all contributors. If someday you come to France, give me
     * a call, an email, anything, and I'll pay you a drink, whoever you are.
     */
    #[\Override]
    public function convertError(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        if (!$error instanceof \PDOException) {
            return $error;
        }

        return self::createErrorFromMessage($error, $sql, $message);
    }
}
