<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

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
                    'table' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add a COLUMN.',
                        'properties' => [
                            'type' => 'string',
                            'nullable' => 'bool',
                            'default' => ['type' =>'string', 'nullable' => true],
                            'collation' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'modify' => [
                        'description' => 'Add a COLUMN.',
                        'properties' => [
                            'type' => ['type' => 'string', 'nullable' => true],
                            'nullable' => ['type' => 'bool', 'nullable' => true],
                            'default' => ['type' =>'string', 'nullable' => true],
                            'drop_default' => ['type' => 'bool', 'default' => false],
                            'collation' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a COLUMN.',
                        'properties' => [
                            'cascade' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Renames a COLUMN.',
                        'properties' => [
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'constraint' => [
                'properties' => [
                    'table' => ['type' => 'string'],
                ],
                'changes' => [
                    'drop' => [
                        'description' => 'Drop an arbitrary constraint from a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'modify' => [
                        'description' => 'Modify an arbitrary constraint on a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'foreign_key' => [
                'properties' => [
                    'table' => ['type' => 'string'],
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add a FOREIGN KEY constraint on a table.',
                        'generate_name' => [
                            'properties' => ['table', 'foreign_table', 'foreign_columns'],
                            'suffix' => 'fk',
                        ],
                        'properties' => [
                            'name' => ['type' => 'string', 'nullable' => true],
                            'columns' => 'string[]',
                            'foreign_table' => 'string',
                            'foreign_columns' => 'string[]',
                            'foreign_schema' => ['type' => 'string', 'nullable' => true],
                            'on_delete' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'on_update' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'modify' => [
                        'description' => 'Modify a FOREIGN KEY constraint on a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'on_delete' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'on_update' => ['enum' => ['set null', 'cascade', 'no action', 'restrict', 'set default'], 'default' => 'no action'],
                            'deferrable' => ['type' => 'bool', 'default' => true],
                            'initially' => ['enum' => ['immediate', 'deferred'], 'default' => 'deferred'],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a FOREIGN KEY constraint from a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'index' => [
                'properties' => [
                    'table' => ['type' => 'string'],
                ],
                'changes' => [
                    'create' => [
                        'description' => 'Create an INDEX on a table.',
                        'generate_name' => [
                            'properties' => ['table', 'columns'],
                            'suffix' => 'idx',
                        ],
                        'properties' => [
                            'name' => ['type' => 'string', 'nullable' => true],
                            'columns' => 'string[]',
                            'type' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop an INDEX from a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'primary_key' => [
                'properties' => [
                    'table' => ['type' => 'string'],
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add the PRIMARY KEY constraint on a table.',
                        'generate_name' => [
                            'properties' => ['table'],
                            'suffix' => 'pkey',
                        ],
                        'properties' => [
                            'name' => ['type' => 'string', 'nullable' => true],
                            'columns' => 'string[]',
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop the PRIMARY KEY constraint from a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'table' => [
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
                'changes' => [
                    'create' => [
                        'description' => 'Create a table.',
                        'properties' => [
                            'columns' => ['type' => 'ColumnAdd[]', 'default' => []],
                            'primary_key' => ['type' => 'PrimaryKeyAdd', 'nullable' => true],
                            'foreign_keys' => ['type' => 'ForeignKeyAdd[]', 'default' => []],
                            'unique_keys' => ['type' => 'UniqueKeyAdd[]', 'default' => []],
                            'indexes' => ['type' => 'IndexCreate[]', 'default' => []],
                            'temporary' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a table.',
                        'properties' => [
                            'cascade' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Renames a table.',
                        'properties' => [
                            'new_name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'unique_key' => [
                'properties' => [
                    'table' => ['type' => 'string'],
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Create a UNIQUE constraint on a table.',
                        'generate_name' => [
                            'properties' => ['table', 'columns'],
                            'suffix' => 'key',
                        ],
                        'properties' => [
                            'name' => ['type' => 'string', 'nullable' => true],
                            'columns' => 'string[]',
                            'nulls_distinct' => ['type' => 'bool', 'default' => true],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a UNIQUE constraint from a table.',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function generateFromFile(): void
    {
        $additional = [];
        foreach ($this->createDefinition() as $objectType => $object) {
            $this->objectType($objectType, $object, $additional);
        }

        $transactionMethodsString = \implode("\n\n", $additional['transaction']['methods']);
        $transactionUse = \implode("\n", $additional['transaction']['use']);

        $schemaTransaction = <<<EOT
            <?php
            
            declare(strict_types=1);
            
            namespace MakinaCorpus\QueryBuilder\Schema\Diff;
            
            {$transactionUse}
            use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;
            use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
            
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
            
            {$transactionMethodsString}
            
                /**
                 * Create a table builder.
                 */
                public function createTable(string \$name): TableBuilder
                {
                    return new TableBuilder(parent: \$this, database: \$this->database, name: \$name, schema: \$this->schema);
                }
            
                /**
                 * Add new arbitrary change.
                 *
                 * @internal
                 *   For builders use only.
                 */
                public function logChange(AbstractChange \$change): void
                {
                    \$this->changeLog->add(\$change);
                }
            }
            EOT;

        \file_put_contents(\dirname(__DIR__, 2) . '/SchemaTransaction.php', $schemaTransaction . "\n");
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

    private function objectType(string $objectType, array $object, array &$additional): array
    {
        $ret = [];
        foreach ($object['changes'] as $operation => $change) {
            $ret[] = $this->objectChange($objectType, $operation, $object, $change, $additional);
        }
        return $ret;
    }

    private function objectChange(string $objectType, string $operation, array $object, array $change, array &$additional): string
    {
        $className = $this->camelize($objectType) . $this->camelize($operation);

        $transactionMethodName = $this->camelize($operation, false) . $this->camelize($objectType);
        $transactionProperties = [];
        $transactionParameters = [];
        $transactionPropertiesWithDefault = [];

        $constructorProperties = [];
        $constructorPropertiesWithDefault = [];
        $propertiesGetters = [];
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

        if (!$methodDescription = ($change['description'] ?? null)) {
            $methodDescription = "Create a new {$className} instance.";
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
            $camelizedPropName = $this->escape($this->camelize($property->name));
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
                } else {
                    $propDefault = ''; // @handle this case.
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
                private readonly {$propType} \${$property->escapedPropName}{$propDefault},
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
                public function {$getterPrefix}{$camelizedPropName}(): $propType
                {
                    return \$this->{$property->escapedPropName};
                }
            EOT;

            /*
             * Transaction.
             */

            $transactionPropertyString = <<<EOT
                {$propType} \${$property->escapedPropName}{$propDefault},
                EOT;

            $transactionParameters[] = <<<EOT
                {$property->escapedPropName}: \${$property->escapedPropName},
                EOT;

            // Position all properties with a default values after the others.
            if ($propDefault) {
                $transactionPropertiesWithDefault[] = $transactionPropertyString;
            } else {
                $transactionProperties[] = $transactionPropertyString;
            }
        }

        // Generate default name method.
        if (isset($change['generate_name'])) {
            $generateNameBody = [];
            if (empty($change['generate_name']['properties'])) {
                throw new QueryBuilderError(\sprintf("%s: generate name callback requires properties.", $className));
            }
            if (empty($change['generate_name']['suffix'])) {
                throw new QueryBuilderError(\sprintf("%s: generate name callback requires a suffix.", $className));
            }

            foreach ($change['generate_name']['properties'] as $propName) {
                $found = null;
                foreach ($properties as $property) {
                    \assert($property instanceof GeneratorProperty);
                    if ($property->name === $propName) {
                        $found = $property;
                        break;
                    }
                }
                if (!$found) {
                    throw new QueryBuilderError(\sprintf("%s: generate name callback '%s' property does not exist.", $className, $propName));
                }
                \assert($found instanceof GeneratorProperty);

                if (['array'] === $found->types) {
                    $generateNamePropAsString = '\implode(\'_\', $this->' . $found->escapedPropName . ')';
                } else if (['string'] === $found->types) {
                    $generateNamePropAsString = '$this->' . $found->escapedPropName;
                } else {
                    throw new QueryBuilderError(\sprintf("%s: generate name callback '%s' property type '%s' is not support.", $className, $propName, \implode('|', $found->types)));
                }

                if ($found->nullable) {
                    $generateNameBody[] = <<<EOT
                                if (\$this->{$found->escapedPropName}) {
                                    \$pieces[] = {$generateNamePropAsString};
                                }
                        EOT;
                } else {
                    $generateNameBody[] = <<<EOT
                                \$pieces[] = {$generateNamePropAsString};
                        EOT;
                }
            }

            $generateNameBodyAsString = \implode("\n", $generateNameBody);
            $generateNameSuffix = $this->escape($change['generate_name']['suffix']);

            $propertiesGetters[] = <<<EOT
                    /**
                     * Used in edge cases, for example when you CREATE INDEX in MySQL,
                     * it requires you to give an index name, but this API doesn't
                     * because almost all RDBMS will generate one for you. This is not
                     * part of the API, it simply help a very few of those edge cases
                     * not breaking.
                     */
                    public function generateName(): string
                    {
                        \$pieces = [];
                {$generateNameBodyAsString}
                        \$pieces[] = '{$generateNameSuffix}';
                
                        return \implode('_', \array_filter(\$pieces));
                    }
                EOT;
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
        
        use MakinaCorpus\\QueryBuilder\\Schema\\Diff\\AbstractChange;
        
        {$description}
        class {$className} extends AbstractChange
        {{$classConstantsString}
            public function __construct(
                string \$database,
                string \$schema,
                $constructorPropertiesString
            ) {
                parent::__construct(
                    database: \$database,
                    schema: \$schema,
                );
            }
        $propertiesGetters
        }
        EOT;

        \file_put_contents(\dirname(__DIR__) . '/' . $className . '.php', $file . "\n");

        /*
         * Transaction methods and code.
         */

        // Table has its own builder, so this cannot be added as a method.
        if ('table' !== $objectType || 'create' !== $operation) {

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

            $additional['transaction']['use'][] = 'use MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change\\' . $className . ';';

            $additional['transaction']['methods'][] = <<<EOT
                    /**
                     * {$methodDescription}
                     */
                    public function {$transactionMethodName}(
                        {$transactionPropertiesString}
                        ?string \$schema = null,
                    ): static {
                        \$this->changeLog->add(
                            new {$className}(
                                {$transactionParametersString}
                                schema: \$schema ?? \$this->schema,
                                database: \$this->database,
                            )
                        );
                
                        return \$this;
                    }
                EOT;
        }

        return $className;
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
            escapedPropName: $this->escape($this->camelize($name, false)),
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
        public readonly string $escapedPropName,
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
