<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

/**
 * Implementation for when the error handling is disabled.
 *
 * Useful when configured over a doctrine/dbal bridge in Symfony context and
 * you want the Query Builder to be absolutely transparently integrated with
 * it. It's also true for other bridges contextes.
 */
class PassthroughErrorConverter implements ErrorConverter
{
    #[\Override]
    public function convertError(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable
    {
        return $error;
    }
}
