<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * LPAD() expression.
 */
class Lpad implements Expression
{
    private Expression $value;
    private Expression $fill;
    private Expression $size;

    /**
     * @param mixed $value
     *   Expression that returns text or a string value.
     * @param int $size
     *   Pad size.
     * @param mixed $fill
     *   Expression to fill with, will be space by default.
     */
    public function __construct(
        mixed $value,
        mixed $size,
        mixed $fill = null,
    ) {
        // @todo Should move type detection to another centralized component.
        $this->value = \is_string($value) ? new Value($value, 'text') : ExpressionHelper::value($value);
        $this->size = \is_int($size) ? new Value($size, 'int') : ExpressionHelper::value($size);
        $this->fill = \is_string($fill) ? new Value($fill, 'text') : ($fill ? ExpressionHelper::value($fill) : new Value(' ',  'text'));
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return 'text';
    }

    /**
     * Get value type if specified.
     */
    public function getValue(): Expression
    {
        return $this->value;
    }

    /**
     * Get value type if specified.
     */
    public function getFill(): Expression
    {
        return $this->fill;
    }

    /**
     * Get pad size.
     */
    public function getSize(): Expression
    {
        return $this->size;
    }
}
