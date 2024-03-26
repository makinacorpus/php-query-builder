<?php

declare(strict_types=1);

namespace Goat\Benchmark\Converter;

use MakinaCorpus\QueryBuilder\DefaultQueryBuilder;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods(["setUp"])]
final class WriterBench
{
    private Writer $writer;
    private Select $querySimple;
    private Select $queryWithJoin;
    private Select $queryBig;

    public function setUp(): void
    {
        $this->writer = new Writer();

        $queryBuilder = new DefaultQueryBuilder();
        $expr = $queryBuilder->expression();

        $this->querySimple = $queryBuilder
            ->select('users')
            ->columnRaw('name')
            ->where('last_login', $expr->currentTimestamp(), '<')
        ;

        $this->queryWithJoin = $queryBuilder
            ->select('users')
            ->join('login', (new Where())->isEqual('users.id', $expr->raw('?::column', ['login.user_id'])))
            ->where('login.last_login', $expr->currentTimestamp(), '<')
        ;

        $this->queryBig = $queryBuilder->select('users');
        $this->queryBig->createColumnAgg('avg', 'login_count')->createOver()->partitionBy('role')->orderBy('loged_at');
        $this->queryBig->join('history', $expr->raw('?::column = ?::column and ?::column < 12', ['users.id', 'history.user_id', 'history.severity']));
        $this->queryBig->getHaving()->isEqual('foo', 'bar')->isBetween($expr->currentTimestamp(), 'history.start', 'history.stop');
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchQuerySimple(): void
    {
        $this->writer->prepare($this->querySimple);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchQueryWithJoin(): void
    {
        $this->writer->prepare($this->queryWithJoin);
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchQueryBig(): void
    {
        $this->writer->prepare($this->queryBig);
    }
}
