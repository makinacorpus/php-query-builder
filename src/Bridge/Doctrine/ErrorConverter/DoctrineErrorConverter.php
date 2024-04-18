<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Doctrine\ErrorConverter;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DatabaseDoesNotExist;
use Doctrine\DBAL\Exception\DatabaseObjectExistsException;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter\PdoSQLiteErrorConverter;
use MakinaCorpus\QueryBuilder\Error\Server\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Server\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\ConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\DatabaseObjectDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\ForeignKeyConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\NotNullConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\UnableToConnectError;
use MakinaCorpus\QueryBuilder\Error\Server\UniqueConstraintViolationError;

class DoctrineErrorConverter implements ErrorConverter
{
    #[\Override]
    public function convertError(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        $message ??= $error->getMessage();
        if ($sql) {
            $message .= "\nQuery was: " . $sql;
        }

        if ($error instanceof InvalidFieldNameException || $error instanceof ColumnDoesNotExist) {
            return new ColumnDoesNotExistError($message, $error->getCode(), $error);
        }

        if ($error instanceof DatabaseDoesNotExist) {
            return new DatabaseObjectDoesNotExistError($message, $error->getCode(), $error);
        }

        if ($error instanceof ForeignKeyConstraintViolationException) {
            return new ForeignKeyConstraintViolationError($message, $error->getCode(), $error);
        }

        if ($error instanceof NotNullConstraintViolationException) {
            return new NotNullConstraintViolationError($message, $error->getCode(), $error);
        }

        if ($error instanceof TableDoesNotExist || $error instanceof TableNotFoundException) {
            return new TableDoesNotExistError($message, $error->getCode(), $error);
        }

        /* if ($error instanceof Foo) {
            return new TransactionDeadlockError();
        } */

        /* if ($error instanceof Foo) {
            return new TransactionLockWaitTimeoutError();
        } */

        if ($error instanceof ConnectionException || $error instanceof ConnectionLost) {
            return new UnableToConnectError($message, $error->getCode(), $error);
        }

        if ($error instanceof UniqueConstraintViolationException) {
            return new UniqueConstraintViolationError($message, $error->getCode(), $error);
        }

        /*
         * More generic errors after.
         */

        if ($error instanceof NonUniqueFieldNameException) {
            return new AmbiguousIdentifierError();
        }

        /* if ($error instanceof Foo) {
            return new TransactionFailedError();
        } */

        if ($error instanceof ConstraintViolationException) {
            return new ConstraintViolationError($message, $error->getCode(), $error);
        }

        if ($error instanceof DatabaseObjectExistsException || $error instanceof DatabaseObjectNotFoundException) {
            return new DatabaseObjectDoesNotExistError($message, $error->getCode(), $error);
        }

        // Provide fallbacks for SQLite, because DBAL don't catch them all.
        return PdoSQLiteErrorConverter::createErrorFromMessage($error, $sql, $message);
    }
}
