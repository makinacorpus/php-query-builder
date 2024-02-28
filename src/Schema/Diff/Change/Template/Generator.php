<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template;

/**
 * Lots of boiler plate DTO code to write, too lazy to do it as a normal
 * person would do, let's generate it instead!
 *
 * This code will probably one-shot, because in the future fixes may be
 * done in those classes code, business stuff will be added, etc...
 *
 * Nevertheless at some point, we might want to add new classes, so we
 * will keep this code in order to be able to generate new classes then.
 */
class Generator
{
    private function createDefinition(): array
    {
        return [
            'column' => [
                'properties' => [
                    'table' => 'string',
                    'name' => 'string',
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add a COLUMN.',
                        'creation' => true,
                        'properties' => [
                            'type' => 'string',
                            'nullable' => 'bool',
                            'default' => ['type' =>'string', 'nullable' => true],
                        ],
                    ],
                    'modify' => [
                        'description' => 'Add a COLUMN.',
                        'creation' => true,
                        'properties' => [
                            'type' => 'string',
                            'nullable' => 'bool',
                            'default' => ['type' =>'string', 'nullable' => true],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a COLUMN.',
                        'creation' => false,
                        'properties' => [
                            'cascade' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Renames a COLUMN.',
                        'creation' => false,
                        'properties' => [
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'constraint' => [
                'properties' => [
                    'name' => 'string',
                    'table' => 'string',
                ],
                'changes' => [
                    'drop' => [
                        'description' => 'Drop an arbitrary constraint from a table.',
                        'creation' => false,
                    ],
                    'modify' => [
                        'description' => 'Modify an arbitrary constraint on a table.',
                        'creation' => false,
                        'properties' => [
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'creation' => false,
                        'properties' => [
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'foreign_key' => [
                'properties' => [
                    'table' => 'string',
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add a FOREIGN KEY constraint on a table.',
                        'creation' => true,
                        'properties' => [
                            'columns' => 'string[]',
                            'foreign_table' => 'string',
                            'foreign_columns' => 'string[]',
                            'foreign_schema' => ['type' => 'string', 'nullable' => true],
                            'name' => ['type' => 'string', 'nullable' => true],
                            'on_delete' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'on_update' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'modify' => [
                        'description' => 'Modify a FOREIGN KEY constraint on a table.',
                        'creation' => false,
                        'properties' => [
                            'on_delete' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'on_update' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a FOREIGN KEY constraint from a table.',
                        'creation' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'creation' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'index' => [
                'properties' => [
                    'table' => 'string',
                ],
                'changes' => [
                    'create' => [
                        'description' => 'Create an INDEX on a table.',
                        'creation' => true,
                        'properties' => [
                            'columns' => 'string[]',
                            'name' => ['type' => 'string', 'nullable' => true],
                            'type' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop an INDEX from a table.',
                        'creation' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'creation' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'primary_key' => [
                'properties' => [
                    'table' => 'string',
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add the PRIMARY KEY constraint on a table.',
                        'creation' => true,
                        'properties' => [
                            'columns' => 'string[]',
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop the PRIMARY KEY constraint from a table.',
                        'creation' => false,
                    ],
                ],
            ],
            'table' => [
                'properties' => [
                    'name' => 'string',
                ],
                'changes' => [
                    'create' => [
                        'description' => 'Create a table.',
                        'creation' => true,
                        'properties' => [
                            'columns' => ['type' => 'ColumnAdd[]', 'default' => []],
                            'primary_key' => ['type' => 'PrimaryKeyAdd', 'nullable' => true],
                            'foreign_keys' => ['type' => 'ForeignKeyAdd[]', 'default' => []],
                            'unique_keys' => ['type' => 'UniqueConstraintAdd[]', 'default' => []],
                            'indexes' => ['type' => 'IndexCreate[]', 'default' => []],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a table.',
                        'creation' => false,
                        'properties' => [
                            'cascade' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Renames a table.',
                        'creation' => false,
                        'properties' => [
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'unique_constraint' => [
                'properties' => [
                    'table' => 'string',
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Create a UNIQUE constraint on a table.',
                        'creation' => true,
                        'properties' => [
                            'columns' => 'string[]',
                            'name' => ['type' => 'string', 'nullable' => true],
                            'nulls_distinct' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a UNIQUE constraint from a table.',
                        'creation' => true,
                        'properties' => [
                            'name' => 'string',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function generateFromFile(): void
    {
        $additional = [];
        foreach ($this->createDefinition() as $prefix => $object) {
            $this->objectType($prefix, $object, $additional);
        }

        $createMethodsString = \implode("\n\n", $additional['manager']['methods']);
        $matcheCasesString = \implode("\n", $additional['manager']['matchCases']);

        $forSchemaManager = <<<EOT
            
            {$createMethodsString}
            
                /**
                 * Apply a given change in the current schema.
                 */
                protected function apply(Change \$change): void
                {
                    \$expression = match (\get_class(\$change)) {
            {$matcheCasesString}
                        default => throw new QueryBuilderError(\sprintf("Unsupported alteration operation: %s", \get_class(\$change))),
                    };
            
                    \$this->executeChange(\$expression);
                }
            
            EOT;

        print $forSchemaManager;

        $createMethodsString = \implode("\n\n", $additional['transaction']['methods']);
        $useString = "\n" . \implode("\n", $additional['transaction']['use']);

        $schemaTransaction = <<<EOT
            <?php
            
            declare(strict_types=1);
            
            namespace MakinaCorpus\QueryBuilder\Schema\Diff;
            
            use MakinaCorpus\QueryBuilder\Schema\SchemaManager;{$useString}
            
            /**
             * This code is generated using bin/generate_changes.php.
             *
             * Please do not modify it manually.
             *
             * @see \\MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change\\Template\\Generator
             * @see bin/generate_changes.php
             */
            class SchemaTransaction
            {
                private ChangeLog \$changeLog;
            
                public function __construct(
                    private readonly SchemaManager \$schemaManager,
                    private readonly string \$database,
                    private readonly string \$schema,
                    private readonly \Closure \$onCommit,
                ) {
                    \$this->changeLog = new ChangeLog(\$schemaManager);
                }
                
                public function commit(): void
                {
                    (\$this->onCommit)(\$this->changeLog->diff());
                }
            
            {$createMethodsString}
            
            }
            EOT;

        \file_put_contents(\dirname(__DIR__, 2) . '/SchemaTransaction.php', $schemaTransaction);
    }

    private function camelize(string $input, bool $first = true): string
    {
        $pieces = \preg_split('/[^a-z0-9]+/ims', $input);
        if (!$first) {
            return \strtolower(\array_shift($pieces)) . \implode('', \array_map(\ucwords(...), $pieces));
        }
        return \implode('', \array_map(\ucwords(...), $pieces));
    }

    private function upperize(string $input): string
    {
        return \implode('_', \array_map(\strtoupper(...), \preg_split('/[^a-z0-9]+/ims', $input)));
    }

    private function escape(string $input): string
    {
        return \addcslashes($input, '\\');
    }

    private function objectType(string $prefix, array $object, array &$additional): array
    {
        $ret = [];
        foreach ($object['changes'] as $suffix => $change) {
            $className = $this->camelize($prefix) . $this->camelize($suffix);
            $this->objectChange($className, $object, $change, $additional);
            $ret[] = $className;
        }
        return $ret;
    }

    private function objectChange(string $className, array $object, array $change, array &$additional): void
    {
        $transactionMethodName = lcfirst($className);
        $transactionProperties = [];
        $transactionParameters = [];
        $transactionPropertiesWithDefault = [];

        $constructorProperties = [];
        $constructorPropertiesWithDefault = [];
        $propertiesGetters = [];
        $creationString = ($change['creation'] ?? false) ? 'true' : 'false';
        $classConstants = [];

        $warning = <<<EOT
             * This code is generated using bin/generate_changes.php.
             *
             * It includes some manually written code, please review changes and apply
             * manual code after each regeneration.
             *
             * @see \\MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change\\Template\\Generator
             * @see bin/generate_changes.php
            EOT;

        if ($description = ($change['description'] ?? null)) {
            $description = <<<EOT
                /**
                 * {$description}
                 *
                {$warning}
                 */
                EOT;
        } else {
            $description = <<<EOT
                /**
                {$warning}
                 */
                EOT;
        }

        $properties = [];
        foreach (($object['properties'] ?? []) as $name => $property) {
            $properties[] = $this->property($name, $property, true);
        }
        foreach (($change['properties'] ?? []) as $name => $property) {
            $properties[] = $this->property($name, $property, false);
        }

        foreach ($properties as $property) {
            $propType = \implode('|', \array_map(fn ($value) => $this->escape($value), $property->types));
            $propName = $this->escape($this->camelize($property->name, false));
            $propCamelized = $this->escape($this->camelize($property->name));
            $propDocType = $property->docType;

            if ($property->nullable) {
                $propType = 'null|' . $propType;
            }

            if (null !== $property->default) {
                if ($property->enumValues || ['string'] === $property->types) {
                    $propDefault = " = '" . $this->escape($property->default) . "'";
                } else if (['bool'] === $property->types) {
                    $propDefault = " = " . (($property->default) ? 'true' : 'false');
                } else if ($property->isArray && [] === $property->default) {
                    $propDefault = ' = []';
                }
            } else if ($property->nullable) {
                $propDefault = ' = null';
            } else {
                $propDefault = '';
            }

            if ($property->enumValues) {
                foreach ($property->enumValues as $value) {
                    $enumCaseName = $this->upperize($property->name . '_' . $value);
                    $classConstants[$property->name][$enumCaseName] = "'" . $this->escape((string) $value) . "'";
                    if ($value === $property->default) {
                        $propDefault = ' = ' . $className . '::' . $enumCaseName;
                    }
                }
            }

            $constructorPropertyString = <<<EOT
                /** @var {$propDocType} */
                EOT;
            $constructorPropertyString .= "\n        ";
            $constructorPropertyString .= <<<EOT
                private readonly {$propType} \${$propName}{$propDefault},
                EOT;

            // Position all properties with a default values after the others.
            if ($propDefault) {
                $constructorPropertiesWithDefault[] = $constructorPropertyString;
            } else {
                $constructorProperties[] = $constructorPropertyString;
            }

            if ($property->isBool()) {
                $getterPrefix = 'is';
            } else {
                $getterPrefix = 'get';
            }

            $propertiesGetters[] = <<<EOT
                /** @return {$propDocType} */
                public function {$getterPrefix}{$propCamelized}(): $propType
                {
                    return \$this->{$propName};
                }
            EOT;

            /*
             * Transaction.
             */

            $transactionPropertyString = <<<EOT
                {$propType} \${$propName}{$propDefault},
                EOT;

            $transactionParameters[] = <<<EOT
                {$propName}: \${$propName},
                EOT;

            // Position all properties with a default values after the others.
            if ($propDefault) {
                $transactionPropertiesWithDefault[] = $transactionPropertyString;
            } else {
                $transactionProperties[] = $transactionPropertyString;
            }
        }

        // Format property getters.
        if ($propertiesGetters) {
            $propertiesGetters = "\n" . \implode("\n\n", $propertiesGetters);
        } else {
            $propertiesGetters = '';
        }

        $constructorPropertiesString = '';
        if ($constructorProperties && $constructorPropertiesWithDefault) {
            $constructorPropertiesString = \implode("\n        ", $constructorProperties);
            $constructorPropertiesString .= "\n        " . \implode("\n        ", $constructorPropertiesWithDefault);
        } else if ($constructorProperties) {
            $constructorPropertiesString = \implode("\n        ", $constructorProperties);
        } else if ($constructorPropertiesWithDefault) {
            $constructorPropertiesString = \implode("\n        ", $constructorPropertiesWithDefault);
        }

        // Format class constants.
        $classConstantsString = '';
        if ($classConstants) {
            \ksort($classConstants);
            foreach ($classConstants as $constants) {
                \ksort($constants);
                foreach ($constants as $constName => $escapedConstValue) {
                    $classConstantsString .= "\n    const " . $constName . ' = ' .$escapedConstValue . ';';
                }
                $classConstantsString .= "\n";
            }
        }

        $file = <<<EOT
        <?php
        
        declare (strict_types=1);
        
        namespace MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change;
        
        use MakinaCorpus\\QueryBuilder\\Schema\\AbstractObject;
        use MakinaCorpus\\QueryBuilder\\Schema\\Diff\\AbstractChange;
        
        {$description}
        class {$className} extends AbstractChange
        {{$classConstantsString}
            public function __construct(
                string \$database,
                string \$schema,
                $constructorPropertiesString
            ) {
                parent::__construct(database: \$database, schema: \$schema);
            }
        $propertiesGetters
        
            #[\\Override]
            public function isCreation(): bool
            {
                return {$creationString};
            }
        
            #[\\Override]
            public function isModified(AbstractObject \$source): bool
            {
                throw new \Exception("Here should be the manually generated code, please revert it.");
            }
        }
        
        EOT;

        \file_put_contents(\dirname(__DIR__) . '/' . $className . '.php', $file);

        /*
         * Manager methods and code.
         */

        $additional['manager']['methods'][] = <<<EOT
                /**
                 * Override if standard SQL is not enough.
                 */
                protected function write{$className}(Change\\{$className} \$change): Expression
                {
                    throw new \Exception("Not implemented yet.");
                }
            EOT;

        $additional['manager']['matchCases'][] = <<<EOT
                        Change\\{$className}::class => \$this->write{$className}(\$change),
            EOT;

        /*
         * Transaction methods and code.
         */

        $transactionPropertiesString = '';
        if ($transactionProperties && $transactionPropertiesWithDefault) {
            $transactionPropertiesString = \implode("\n        ", $transactionProperties);
            $transactionPropertiesString .= "\n        " . \implode("\n        ", $transactionPropertiesWithDefault);
        } else if ($transactionProperties) {
            $transactionPropertiesString = \implode("\n        ", $transactionProperties);
        } else if ($transactionPropertiesWithDefault) {
            $transactionPropertiesString = \implode("\n        ", $transactionPropertiesWithDefault);
        }

        $transactionParametersString = \implode("\n                ", $transactionParameters);

        $additional['transaction']['methods'][] = <<<EOT
                public function {$transactionMethodName}(
                    {$transactionPropertiesString}
                    ?string \$schema = null,
                ): static {
                    \$this->changeLog->add(
                        new {$className}(
                            {$transactionParametersString}
                            schema: \$schema ?? \$this->schema,
                            database: \$this->database,
                        ),
                    );
            
                    return \$this;
                }
            EOT;

        $additional['transaction']['use'][] = 'use MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change\\' . $className . ';';
    }

    private function property(string $name, string|array $property, bool $parent = false): GeneratorProperty
    {
        if (\is_string($property)) {
            $property = ['type' => $property];
        }
        if (empty($property['type'])) {
            if (empty($property['enum'])) {
                $property['type'] = ['mixed'];
            } else {
                $property['type'] = ['string'];
            }
        } else if (\is_string($property['type'])) {
            $property['type'] = \explode('|', $property['type']);
        }

        $isArray = false;

        $docTypes = [];
        foreach ($property['type'] as $key => $type) {
            if (\str_ends_with($type, '[]')) {
                $type = \substr($type, 0, -2);
                $docTypes[] = 'array<' . $type . '>';
                $isArray = true;
                $property['type'][$key] = 'array';
            } else {
                $docTypes[] = $type;
            }
        }

        return new GeneratorProperty(
            default: $property['default'] ?? null,
            docType: \implode('|', $docTypes),
            enumValues: $property['enum'] ?? [],
            isArray: $isArray,
            name: $name,
            nullable: $property['nullable'] ?? false,
            parent: $parent,
            types: $property['type'],
        );
    }
}

class GeneratorProperty
{
    public function __construct(
        public readonly string $name,
        public readonly array $types,
        public readonly string $docType,
        public readonly null|bool|string|array $default = null,
        public readonly array $enumValues = [],
        public readonly bool $isArray = false,
        public readonly bool $nullable = false,
        public readonly bool $parent = false,
    ) {}

    public function isBool(): bool
    {
        return ['bool'] === $this->types;
    }
}
