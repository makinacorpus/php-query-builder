<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Escaper;

class MySQLEscaper extends StandardEscaper
{
    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        return '`' . \str_replace('`', '``', $string) . '`';
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '`',  // Identifier escape character
            '\'', // String literal escape character
            '"',  // String literal variant
        ];
    }
}
