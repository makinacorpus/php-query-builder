<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\CaseWhen;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\RawQuery;

class ExpressionFactoryTest extends UnitTestCase
{
    protected function createQuery(): Query
    {
        return new RawQuery('select 1');
    }

    public function testAliasedStatic(): void
    {
        self::assertInstanceOf(Aliased::class, ExpressionFactory::aliased('bar', 'foo'));
    }

    public function testAliasedViaQuery(): void
    {
        self::assertInstanceOf(Aliased::class, $this->createQuery()->expression()->aliased('bar', 'foo'));
    }

    public function testArrayStatic(): void
    {
        self::assertInstanceOf(ArrayValue::class, ExpressionFactory::array([]));
    }

    public function testArrayViaQuery(): void
    {
        self::assertInstanceOf(ArrayValue::class, $this->createQuery()->expression()->array([]));
    }

    public function testCaseWhenStatic(): void
    {
        self::assertInstanceOf(CaseWhen::class, ExpressionFactory::caseWhen());
    }

    public function testCaseWhenViaQuery(): void
    {
        self::assertInstanceOf(CaseWhen::class, $this->createQuery()->expression()->caseWhen());
    }
}
