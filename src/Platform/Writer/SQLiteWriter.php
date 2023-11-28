<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Lpad;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * SQLite.
 *
 * Tested with SQLite >= 3.
 */
class SQLiteWriter extends Writer
{
    /**
     * Format a function call.
     *
     * This is non standard SQL, and returns the PostgreSQL variant.
     */
    protected function formatRandom(Random $expression, WriterContext $context): string
    {
        return 'random()';
    }

    /**
     * Format a function call.
     */
    protected function formatRandomInt(RandomInt $expression, WriterContext $context): string
    {
        $min = $expression->getMin();
        $max = $expression->getMax();

        if ($max < $min) {
            $max = $min;
            $min = $expression->getMax();
        }

        return $this->formatRaw(
            new Raw(
                'abs(random()) % (? - ?) + ?',
                [$max, $min, $min]
            ),
            $context,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function formatLpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        // @see https://stackoverflow.com/questions/6576343/how-to-emulate-lpad-rpad-with-sqlite
        return $this->formatRaw(
            new Raw(
                <<<SQL
                substr(
                    replace(
                        hex(zeroblob(?)),
                        '00',
                        ?
                    ), 1, ? - length(?)
                ) || ?
                SQL,
                [$size, $fill, $size, $value, $value],
            ),
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function formatRpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        // @see https://stackoverflow.com/questions/6576343/how-to-emulate-lpad-rpad-with-sqlite
        return $this->formatRaw(
            new Raw(
                <<<SQL
                ? || substr(
                    replace(
                        hex(zeroblob(?)),
                        '00',
                        ?
                    ), 1, ? - length(?)
                )
                SQL,
                [$value, $size, $fill, $size, $value],
            ),
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * {@inheritdoc}
     *
     * SQLite does not support OFFSET alone.
     */
    protected function doFormatRange(WriterContext $context, int $limit = 0, int $offset = 0, bool $hasOrder = true): string
    {
        if ($limit) {
            if (!$offset) {
                return 'limit ' . $limit;
            }
            return 'limit ' . $limit . ' offset ' . $offset;
        }
        if ($offset) {
            return 'limit -1 offset ' . $offset;
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function formatDelete(Delete $query, WriterContext $context): string
    {
        $from = $query->getAllFrom();

        if (1 < \count($from)) {
            throw new QueryBuilderError("SQLite doesn't support DELETE USING|JOIN");
        }

        $join = $query->getAllJoin();
        if ($join) {
            throw new QueryBuilderError("SQLite doesn't support DELETE USING|JOIN");
        }

        return parent::formatDelete($query, $context);
    }
}
