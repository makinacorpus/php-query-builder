<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Version;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

/**
 * RDMBS identification.
 *
 * One could have used an enum here, but we want server identification
 * string to be arbitrary in various places in the code, to allow this
 * API to remain extensible.
 */
class Version
{
    private int $major = 0;
    private int $minor = 0;
    private int $patch = 0;
    private int $precision = 3;

    public function __construct(string $version)
    {
        $matches = [];
        if (!\preg_match('@(\d+)(\.(\d+)|)(\.(\d+)|)@', $version, $matches)) {
            throw new QueryBuilderError(\sprintf("Version '%s', is not in 'x.y.z' semantic format", $version));
        }

        $this->major = (int) $matches[1];

        if (isset($matches[3]) && $matches[3] !== '') {
            $this->minor = (int) $matches[3];
            if (isset($matches[5]) && $matches[5] !== '') {
                $this->patch = (int) $matches[5];
                $this->precision = 3;
            } else {
                $this->precision = 2;
            }
        } else {
            $this->precision = 1;
        }
    }

    /**
     * Versions cannot be simply
     */
    private function compareTo(string|Version $other): int
    {
        $other = \is_string($other) ? new Version($other) : $other;

        $precision = \min($this->precision, $other->precision);

        if (1 === $precision || $this->major !== $other->major) {
            return $this->major - $other->major;
        }
        if (2 === $precision || $this->minor !== $other->minor) {
            return $this->minor - $other->minor;
        }
        return $this->patch - $other->patch;
    }

    /**
     * Is the given version OP this version?
     */
    public function compare(string|Version $other, string $operator = '='): bool
    {
        $compare = $this->compareTo($other);

        return match ($operator) {
            '<' => 0 < $compare,
            '<=' => 0 <= $compare,
            '=' => 0 === $compare,
            '>=' => 0 >= $compare,
            '>' => 0 > $compare,
            default => throw new QueryBuilderError("Version comparison operator must be one of '<', '<=', '=', '>=', '>'"),
        };
    }
}
