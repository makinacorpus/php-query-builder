<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\OptionsBag;

class ConverterContext
{
    private ?string $phpDefaultTimeZone = null;

    public function __construct(
        private Converter $converter,
        private ?OptionsBag $options = null,
    ) {}

    public function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getClientTimeZone(): string
    {
        // @phpstan-ignore-next-line
        return $this->options?->get('client_timezone') ?? ($this->phpDefaultTimeZone ??= (@\date_default_timezone_get()) ?? 'UTC');
    }

    public function getClientEncoding(): string
    {
        return $this->options?->get('client_encoding') ?? 'UTF-8';
    }
}
