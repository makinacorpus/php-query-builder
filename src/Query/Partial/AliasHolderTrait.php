<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\TableExpression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\WithAlias;

/**
 * Aliasing and conflict deduplication logic.
 */
trait AliasHolderTrait
{
    private int $aliasIndex = 0;
    /** @var array<string,string> */
    private array $tableIndex = [];

    /**
     * Create a new alias for the given symbol name.
     *
     * This function does not register the newly created alias as being used
     * in the query, it just create a new predictible alias.
     *
     * If the user given symbol name already exists, an arbitrary new new will
     * be given.
     */
    public function createAlias(string $name): string
    {
        if (isset($this->tableIndex[$name])) {
            return $this->createArbitraryAlias($name, false);
        }
        return $name;
    }

    /**
     * Normalize table to an expression with a given or generated alias.
     *
     * @param string|Expression $table
     *   A table name, a local alias, or an arbitrary expression instance.
     */
    protected function normalizeTable(mixed $table, ?string $alias = null): WithAlias|TableExpression
    {
        $expression = ExpressionHelper::table($table);

        if ($expression instanceof TableName) {
            $computedAlias = $this->createAliasForName($expression->getName(), $alias ?? $expression->getAlias());

            // Avoid useless SELECT "foo" AS "foo" which duplicates the
            // table name.
            if ($computedAlias === $expression->getName()) {
                return $expression;
            }

            // Create a new instance with fixed alias, in case the user gave
            // an explicit one, or if the table didn't already had one.
            return new TableName(
                $expression->getName(),
                $computedAlias,
                $expression->getNamespace(),
            );
        }

        // Name needs to be unique, we will lookup for table names.
        $expressionName = '<nested raw expression ' . \uniqid('', true) . '>';

        if ($expression instanceof WithAlias) {
            return $expression->cloneWithAlias(
                $this->createAliasForName(
                    $expressionName,
                    $alias ?? $expression->getAlias()
                )
            );
        }

        return new Aliased(
            $expression,
            $this->createAliasForName($expressionName, $alias)
        );
    }

    /**
     * Normalize table to an table expression with a given or generated alias.
     *
     * @param string|TableExpression $table
     *   A table name, a local alias, or an arbitrary expression instance.
     */
    protected function normalizeStrictTable(string|Expression $table, ?string $alias = null): TableName
    {
        $expression = ExpressionHelper::tableName($table);
        $computedAlias = $this->createAliasForName($table, $alias ?? $expression->getAlias());

        // Avoid useless SELECT "foo" AS "foo" which duplicates the
        // table name.
        if ($computedAlias === $expression->getName()) {
            return $expression;
        }

        return new TableName($expression->getName(), $alias, $expression->getNamespace());
    }

    /**
     * Create an arbitrary unique alias.
     */
    protected function createArbitraryAlias(string $name, bool $register = true): string
    {
        $alias = 'mcqb_' . ++$this->aliasIndex;
        if ($register) {
            $this->tableIndex[$alias] = $name;
        }

        return $alias;
    }

    /**
     * Create a new alias for the given symbol name.
     *
     * If the user given symbol name already exists, an arbitrary new new will
     * be given.
     *
     * If the user given alias is already in use, an exception will be thrown.
     */
    protected function createAliasForName(string $name, ?string $alias = null): string
    {
        if ($alias) {
            if (isset($this->tableIndex[$alias])) {
                throw new QueryBuilderError(\sprintf(
                    "Alias '%s' is already registered for table '%s'",
                    $alias,
                    $this->tableIndex[$alias],
                ));
            }

            $this->tableIndex[$alias] = $name;

            return $alias;
        }

        // Avoid conflicting table names.
        if ('<nested' !== \substr($name, 0, 7) && false === \array_search($name, $this->tableIndex)) {
            $this->tableIndex[$name] = $name;

            return $name;
        }

        // Worst case scenario, we have to create an arbitry alias that the
        // user cannot guess, but which will prevent conflicts.
        return $this->createArbitraryAlias($name);
    }

    /**
     * Remove known alias from this query.
     */
    protected function removeAlias(string $alias): void
    {
        unset($this->tableIndex[$alias]);
    }
}
