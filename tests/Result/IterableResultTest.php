<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Result\IterableResult;
use MakinaCorpus\QueryBuilder\Result\Result;

class IterableResultTest extends AbstractResultTest
{
    protected function createResult(): Result
    {
        return new IterableResult(new \ArrayIterator([
            ['a' => 'key1', 'b' => 'val1'],
            ['a' => 'key2', 'b' => 'val2'],
        ]));
    }
}
