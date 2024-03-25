<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Add interval to date.
 */
class DateAdd implements Expression
{
    private Expression $date;
    private Expression $interval;

    public function __construct(
        mixed $date,
        mixed $interval,
    ) {
        $this->date = ExpressionHelper::date($date);
        $this->interval = ExpressionHelper::interval($interval);
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::timestamp();
    }

    public function getDate(): Expression
    {
        return $this->date;
    }

    public function getInterval(): Expression
    {
        return $this->interval;
    }

    public function __clone(): void
    {
        $this->date = clone $this->date;
        $this->interval = clone $this->interval;
    }
}
