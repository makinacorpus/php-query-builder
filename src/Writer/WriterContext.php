<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Writer;

use MakinaCorpus\QueryBuilder\ArgumentBag;
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Type\Type;

final class WriterContext
{
    private ArgumentBag $arguments;
    private int $currentIndex = 0;

    public function __construct(Converter $converter)
    {
        $this->arguments = new ArgumentBag($converter);
    }

    public function append(mixed $value, ?Type $type = null): int
    {
        $this->arguments->add($value, $type);

        return $this->currentIndex++;
    }

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    public function getArgumentBag(): ArgumentBag
    {
        return $this->arguments;
    }
}
