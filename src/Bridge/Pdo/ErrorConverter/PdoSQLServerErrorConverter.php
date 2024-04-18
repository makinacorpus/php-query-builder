<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\ErrorConverter;

use MakinaCorpus\QueryBuilder\Bridge\ErrorConverter;
use MakinaCorpus\QueryBuilder\Error\Server\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Server\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\DatabaseObjectDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\ForeignKeyConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\NotNullConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Server\ServerError;
use MakinaCorpus\QueryBuilder\Error\Server\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Server\UnableToConnectError;
use MakinaCorpus\QueryBuilder\Error\Server\UniqueConstraintViolationError;

class PdoSQLServerErrorConverter implements ErrorConverter
{
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

        switch ($errorCode) {
            /* case 102:
                return new SyntaxErrorException($exception, $query); */

            case 207:
                return new ColumnDoesNotExistError($error->getMessage(), (int) $errorCode, $error);

            case 208:
                return new TableDoesNotExistError($error->getMessage(), (int) $errorCode, $error);

            case 209:
                return new AmbiguousIdentifierError($error->getMessage(), (int) $errorCode, $error);

            case 515:
                return new NotNullConstraintViolationError($error->getMessage(), (int) $errorCode, $error);

            case 547:
            case 4712:
                return new ForeignKeyConstraintViolationError($error->getMessage(), (int) $errorCode, $error);

            case 2601:
            case 2627:
                return new UniqueConstraintViolationError($error->getMessage(), (int) $errorCode, $error);

            case 2714:
                return new TableDoesNotExistError($error->getMessage(), (int) $errorCode, $error);

            case 3701:
            case 15151:
                return new DatabaseObjectDoesNotExistError($error->getMessage(), (int) $errorCode, $error);

            case 11001:
            case 18456:
                return new UnableToConnectError($error->getMessage(), (int) $errorCode, $error);
        }

        return new ServerError($error->getMessage(), $errorCode, $error);
    }
}
