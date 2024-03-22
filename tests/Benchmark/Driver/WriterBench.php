<?php

declare(strict_types=1);

namespace Goat\Benchmark\Converter;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Writer\Writer;

/**
 * @BeforeMethods({"setUp"})
 */
final class WriterBench
{
    private Writer $writer;
    private Query $query;

    public function setUp(): void
    {
        $this->writer = new Writer();

        $query = new Select('task', 't');
        $query->column('t.*');
        $query->column('n.type');
        $query->columnAgg('avg', 'views');
        $query->column(new Raw('count(n.id)'), 'comment_count');
        // Add and remove a column for fun
        $query->column('some_field', 'some_alias')->removeColumn('some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy(new Raw('count(n.nid)'), Query::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->getWhere();
        $where->isEqual('t.user_id', 12);
        $where->isLess('t.deadline', new Raw('current_timestamp'));
        $having = $query->getHaving();
        $having->withRaw('count(n.nid) < ?', 3);

        $this->query = $query;
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchArbitrary(): void
    {
        $this->writer->prepare($this->query);
    }
}
