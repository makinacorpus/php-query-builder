<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

class ConverterContext
{
    public function __construct(
        private Converter $converter
    ) {}

    public function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getClientTimeZone(): string
    {
        // @todo Make this configurable.
        return (@\date_default_timezone_get()) ?? 'UTC';
    }

    public function getClientEncoding(): string
    {
        // @todo Make this configurable.
        return 'UTF-8';
    }
}
