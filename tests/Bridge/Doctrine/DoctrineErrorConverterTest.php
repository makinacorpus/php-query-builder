<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use MakinaCorpus\QueryBuilder\Testing\FunctionalDoctrineTestCaseTrait;
use MakinaCorpus\QueryBuilder\Tests\Bridge\AbstractErrorConverterTestCase;

class DoctrineErrorConverterTest extends AbstractErrorConverterTestCase
{
    use FunctionalDoctrineTestCaseTrait;
}
