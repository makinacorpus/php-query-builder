<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter;

use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Error\Server\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Server\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\ForeignKeyConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\NotNullConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\ServerError;
use MakinaCorpus\QueryBuilder\Error\Server\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\TransactionDeadlockError;
use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;
use MakinaCorpus\QueryBuilder\Error\Server\UniqueConstraintViolationError;

class PdoPostgreSQLErrorConverter implements ErrorConverter
{
    /**
     * @link http://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
     *
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

        $message ??= $error->getMessage();
        if ($sql) {
            $message .= "\nQuery was: " . $sql;
        }

        $errorCode = $error->errorInfo[1] ?? $error->getCode();
        $sqlState = $error->errorInfo[0] ?? $error->getCode();

        switch ($sqlState) {

            case '40001':
            case '40P01':
                return new TransactionDeadlockError($message, $errorCode, $error);

            case '0A000':
                // Foreign key constraint violations during a TRUNCATE operation
                // are considered "feature not supported" in PostgreSQL.
                if (\strpos($error->getMessage(), 'truncate') !== false) {
                    return new ForeignKeyConstraintViolationError($message, $errorCode, $error);
                }

                break;

            case '23502':
                return new NotNullConstraintViolationError($message, $errorCode, $error);

            case '23503':
                return new ForeignKeyConstraintViolationError($message, $errorCode, $error);

            case '23505':
                return new UniqueConstraintViolationError($message, $errorCode, $error);

            /*
            case '42601':
                // Syntax error.
             */

            case '42702':
                return new AmbiguousIdentifierError($message, $errorCode, $error);

            case '42703':
                return new ColumnDoesNotExistError($message, $errorCode, $error);

            /*
            case '42703':
                // Invalid identifier.
             */

            case '42P01':
                return new TableDoesNotExistError($message, $errorCode, $error);

            /*
            case '42P07':
                // Table exists.
             */

            /*
            case '7':
                // In some case (mainly connection errors) the PDO exception does not provide a SQLSTATE via its code.
                // The exception code is always set to 7 here.
                // We have to match against the SQLSTATE in the error message in these cases.
                if (\strpos($error->getMessage(), 'SQLSTATE[08006]') !== false) {
                    // Connection error.
                }

                break;
             */
        }

        // Attempt with classes if we do not handle the specific SQL STATE code.
        switch (\substr($sqlState, 2)) {

            case '40':
                return new TransactionError($message, $errorCode, $error);
        }

        return new ServerError($message, $errorCode, $error);
    }
}
