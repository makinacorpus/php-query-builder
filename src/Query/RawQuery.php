<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Raw query is strictly identical to RawExpression with the additional
 * implementation of the Query interface.
 *
 * Since we don't know in advance if the query will return something
 * (expression) or not (statement), we type with Expression.
 */
class RawQuery extends AbstractQuery
{
    private array $arguments;

    public function __construct(
        private string $expression,
        mixed $arguments = null
    ) {
        $this->expression = $expression;
        $this->arguments = ExpressionHelper::arguments($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows(): bool
    {
        // Since we cannot predict what the user will write here, it's safe
        // to always return true here, the only consequence is that it might
        // bypass a few optimisations in rare cases.
        return true;
    }

    /**
     * Get raw SQL string
     */
    public function getString(): string
    {
        return $this->expression;
    }

    /**
     * Get arguments.
     *
     * @return mixed[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        foreach ($this->arguments as $index => $value) {
            $this->arguments[$index] = \is_object($value) ? clone $value : $value;
        }
    }
}
