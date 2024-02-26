<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge;

interface ErrorConverter
{
    /**
     * Convert underlaying connector errors to a common error.
     *
     * @param \Throwable $error
     *   Original error from driver.
     * @param null|string $sql
     *   The raw SQL query that is the subject of this error.
     * @param null|string $message
     *   Overriden error message, if any.
     */
    public function convertError(\Throwable $error, ?string $sql = null, ?string $message = null): \Throwable;
}
