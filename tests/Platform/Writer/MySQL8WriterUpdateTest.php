<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Writer;

use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer;

class MySQL8WriterUpdateTest extends MySQLWriterUpdateTest
{
    protected function setUp(): void
    {
        self::setTestWriter(new MySQL8Writer(new StandardEscaper('#', 1)));
    }

    protected function tearDown(): void
    {
        self::setTestWriter(null);
    }
}
