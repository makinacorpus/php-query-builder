<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use MakinaCorpus\QueryBuilder\Testing\FunctionalDoctrineTestCaseTrait;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class DoctrineTestCase extends FunctionalTestCase
{
    use FunctionalDoctrineTestCaseTrait;
}
