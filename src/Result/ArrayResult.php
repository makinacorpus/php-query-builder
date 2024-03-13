<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

class ArrayResult extends AbstractResult
{
    private ?int $columnCount = null;

    public function __construct(
        private array $data
    ) {
        parent::__construct(false);

        $this->columnCount = $this->data ? \count(\current($this->data)) : 0;
        \reset($this->data);
    }

    #[\Override]
    protected function doRowCount(): int
    {
        return \count($this->data);
    }

    #[\Override]
    protected function doColumnCount(): int
    {
        return $this->columnCount;
    }

    #[\Override]
    protected function doFree(): void
    {
        $this->data = [];
    }

    #[\Override]
    protected function doFetchNext(): null|array
    {
        $ret = \current($this->data);
        \next($this->data);

        return \is_array($ret) ? $ret : null;
    }
}
