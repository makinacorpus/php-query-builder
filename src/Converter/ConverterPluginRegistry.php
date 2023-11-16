<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\DateInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\IntervalInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\RamseyUuidInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\SymfonyUidInputConverter;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * This component will be shared to all bridges when bundled in Symfony,
 * this registry allows the user to add its custom implementations of
 * converter plugins only once.
 */
class ConverterPluginRegistry
{
    /** @var array<string,array<InputConverter>> */
    private array $inputConverters = [];
    /** @var array<InputTypeGuesser> */
    private array $typeGuessers = [];

    /** @param iterable<ConverterPlugin> $plugins */
    public function __construct(?iterable $plugins = null)
    {
        if (null !== $plugins) {
            foreach ($plugins as $plugin) {
                $this->register($plugin);
            }
        }

        // Register defaults.
        $this->register(new DateInputConverter());
        $this->register(new IntervalInputConverter());
        if (\class_exists(UuidInterface::class)) {
            $this->register(new RamseyUuidInputConverter());
        }
        if (\class_exists(AbstractUid::class)) {
            $this->register(new SymfonyUidInputConverter());
        }
    }

    /**
     * Register a custom value converter.
     */
    public function register(ConverterPlugin $plugin): void
    {
        $found = false;
        if ($plugin instanceof InputConverter) {
            $found = true;

            foreach ($plugin->supportedInputTypes() as $type) {
                $this->inputConverters[$type][] = $plugin;
            }
        }

        if ($plugin instanceof InputTypeGuesser) {
            $found = true;

            $this->typeGuessers[] = $plugin;
        }

        if (!$found) {
            throw new \InvalidArgumentException(\sprintf("Unsupported plugin class %s", \get_class($plugin)));
        }
    }

    /** @return iterable<InputConverter> */
    public function getInputConverters(string $type): iterable
    {
        return $this->inputConverters[$type] ?? [];
    }

    /** @return iterable<InputTypeGuesser> */
    public function getTypeGuessers(): iterable
    {
        return $this->typeGuessers;
    }
}
