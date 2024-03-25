<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Guess wihch SQL type will apply to given PHP value.
 */
interface InputTypeGuesser extends ConverterPlugin
{
    /**
     * Guess input type of given value.
     */
    public function guessInputType(mixed $value): null|string|Type;
}
