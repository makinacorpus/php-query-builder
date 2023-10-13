<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Driver;

use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

class PdoDriverTest extends FunctionalTestCase
{
    public function testStupid(): void
    {
        $result = $this->getQueryBuilder()->select()->columnRaw('1')->execute();

        $count = 0;
        foreach ($result as $value) {
            $count++;
        }

        self::assertSame(1, $count);
    }
}
