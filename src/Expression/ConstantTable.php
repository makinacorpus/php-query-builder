<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\TableExpression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Constant table expression reprensent one or more rows of raw arbitrary
 * values, them that you would write in INSERT or MERGE queries after the
 * VALUES () keyword.
 *
 * PostgreSQL among others allows to use VALUES (...) [, ...] expressions
 * in place of tables in queries. This class allows you to build such
 * expressions.
 *
 * You can alias columns, using ->columns(['foo', 'bar']) but it wont be
 * used except when you SELECT on those constant tables.
 *
 * In all case, values to rows conversion will be done lazily while formatting.
 *
 * Depending upon the SQL server implementation, you may use this in place of
 * any table name expression, anywhere.
 */
class ConstantTable implements TableExpression
{
    private ?int $columnCount = null;
    private array $addedRows = [];

    public function __construct(
        private mixed $rows = null,
        private ?array $columns = null,
    ) {
        if (null !== $columns) {
            $this->columnCount = \count($columns);
        }
    }

    /**
     * Set columns.
     */
    public function columns(array $columns): static
    {
        if (null !== $this->columns) {
            throw new QueryBuilderError("Columns are already set in constant table.");
        }
        $this->columns = $columns;
        $this->columnCount = \count($columns);

        return $this;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    /**
     * Add arbitrary row.
     *
     * @param mixed $values
     *   When given a callback, execute first, and apply the following on
     *   the callback result.
     *   When given a Row instance, well, it's row.
     *   When given an array or iterator, each value is a row value.
     *   When given anything else, it will be a single-value row.
     *
     * Values can be anything that will be converted to a value later,
     * including expression instances.
     *
     * Value to row conversion will be done while formatting.
     */
    public function row(mixed $values): static
    {
        $this->addedRows[] = $values;

        return $this;
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    /**
     * Get column names.
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * Prepare row.
     */
    private function prepareRow(mixed $row, int $index): Row
    {
        if (!$row instanceof Row) {
            $row = new Row($row);
        }

        $rowColumnCount = $row->getColumnCount();

        if (null === $this->columnCount) {
            $this->columnCount = $rowColumnCount;
        } else if ($this->columnCount !== $rowColumnCount) {
            throw new QueryBuilderError(\sprintf(
                "Row at index #%d column count %d mismatches from constant table column count %d",
                $index,
                $rowColumnCount,
                $this->columnCount,
            ));
        }

        return $row;
    }

    /**
     * Get rows.
     *
     * @return \Iterator<Row>
     */
    public function getRows(): \Iterator
    {
        $index = 0;

        $rows = $this->rows;
        if (null !== $rows) {
            if (\is_callable($rows)) {
                $rows = ($rows)();
            }
            if (null !== $rows && !\is_iterable($rows)) {
                throw new QueryBuilderError("Rows initializer is not iterable, or was a callback which didn't return an iterable.");
            }
        }

        if ($rows) {
            foreach ($rows as $row) {
                yield $this->prepareRow($row, $index++);
            }
        }
        foreach ($this->addedRows as $row) {
            yield $this->prepareRow($row, $index++);
        }
    }
}
