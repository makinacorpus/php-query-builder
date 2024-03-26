<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Testing\FunctionalPdoTestCaseTrait;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class PdoTestCase extends FunctionalTestCase
{
    use FunctionalPdoTestCaseTrait;
}
