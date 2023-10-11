<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

/**
 * Represent a table expression, of any form, which may be a table name
 * identifier or a constant table.
 *
 * SELECT queries are table expressions as well and can be used in queries
 * as such.
 */
interface TableExpression extends Expression
{
}
