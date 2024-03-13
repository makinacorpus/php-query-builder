<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Bridge\Pdo\Escaper;

class PdoMySQLEscaper extends PdoEscaper
{
    #[\Override]
    protected function areIdentifiersSafe(): bool
    {
        return false;
    }

    #[\Override]
    public function escapeIdentifier(string $string): string
    {
        return '`' . \str_replace('`', '``', $string) . '`';
    }

    #[\Override]
    public function getEscapeSequences(): array
    {
        return [
            '`',  // Identifier escape character
            '\'', // String literal escape character
            '"',  // String literal variant
        ];
    }
}
