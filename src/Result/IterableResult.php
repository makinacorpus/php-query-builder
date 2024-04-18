<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

class IterableResult extends AbstractResult
{
    private ?int $columnCount = null;
    private null|array $current = null;
    private bool $currentFetched = false;
    private ?\Iterator $nextGenerator;
    private mixed /* callable */ $freeCallback;

    public function __construct(
        iterable $data,
        private ?int $rowCount = null,
        callable $freeCallback = null,
    ) {
        parent::__construct(false);

        $this->nextGenerator = (fn () => yield from $data)();
        $this->freeCallback = $freeCallback;
    }

    /**
     * Get current result.
     */
    private function current(): null|array
    {
        if ($this->currentFetched) {
            $this->doFetchNext();
            $this->currentFetched = true;
        }
        return $this->current;
    }

    #[\Override]
    protected function doRowCount(): int
    {
        return $this->rowCount ?? -1;
    }

    #[\Override]
    protected function doColumnCount(): int
    {
        return $this->columnCount ??= (($current = $this->current()) ? \count($current) : 0);
    }

    #[\Override]
    protected function doFree(): void
    {
        $this->current = null;
        $this->nextGenerator = null;
        if ($this->freeCallback) {
            ($this->freeCallback)();
        }
        $this->freeCallback = null;
    }

    #[\Override]
    protected function doFetchNext(): null|array
    {
        if (!$this->currentFetched) {
            $this->currentFetched = false;
            $this->current = $this->nextGenerator?->current();
            $this->nextGenerator?->next();
        }

        return $this->current;
    }
}
