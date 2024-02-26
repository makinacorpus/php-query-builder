<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use MakinaCorpus\QueryBuilder\Testing\FunctionalPdoTestCaseTrait;
use MakinaCorpus\QueryBuilder\Tests\Bridge\AbstractErrorConverterTestCase;

class PdoErrorConverterTest extends AbstractErrorConverterTestCase
{
    use FunctionalPdoTestCaseTrait;
}
