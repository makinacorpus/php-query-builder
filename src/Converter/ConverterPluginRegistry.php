<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter;

use MakinaCorpus\QueryBuilder\Converter\InputConverter\DateInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\IntervalInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\RamseyUuidInputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputConverter\SymfonyUidInputConverter;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter\DateOutputConverter;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter\RamseyUuidOutputConverter;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter\SymfonyUidOutputConverter;
use MakinaCorpus\QueryBuilder\Error\ConfigurationError;
use MakinaCorpus\QueryBuilder\Type\Type;
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
    /** @var array<InputConverter> */
    private array $wildcardInputConverters = [];
    /** @var array<string,array<OutputConverter>> */
    private array $outputConverters = [];
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
        $this->register(new DateOutputConverter());
        $this->register(new IntervalInputConverter());
        if (\interface_exists(UuidInterface::class)) {
            $this->register(new RamseyUuidInputConverter());
            $this->register(new RamseyUuidOutputConverter());
        }
        if (\class_exists(AbstractUid::class)) {
            $this->register(new SymfonyUidInputConverter());
            $this->register(new SymfonyUidOutputConverter());
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
                if ('*' === $type) {
                    $this->wildcardInputConverters[] = $plugin;
                } else {
                    $this->inputConverters[Type::create($type)->getArbitraryName()][] = $plugin;
                }
            }
        }

        if ($plugin instanceof OutputConverter) {
            $found = true;
            foreach ($plugin->supportedOutputTypes() as $type) {
                $this->outputConverters[$type][] = $plugin;
            }
        }

        if ($plugin instanceof InputTypeGuesser) {
            $found = true;
            $this->typeGuessers[] = $plugin;
        }

        if (!$found) {
            throw new ConfigurationError(\sprintf("Unsupported plugin class %s", \get_class($plugin)));
        }
    }

    /** @return InputConverter[] */
    public function getInputConverters(?Type $type): iterable
    {
        if (!$type) {
            return $this->wildcardInputConverters;
        }
        return $this->inputConverters[$type->getArbitraryName()] ?? [];
    }

    /** @return OutputConverter[] */
    public function getOutputConverters(?string $type): iterable
    {
        return $this->outputConverters[$type] ?? [];
    }

    /** @return InputTypeGuesser[] */
    public function getTypeGuessers(): iterable
    {
        return $this->typeGuessers;
    }
}
