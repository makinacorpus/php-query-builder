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
                    'columns' => 'string[]',
                    'foreign_table' => 'string',
                    'foreign_columns' => 'string[]',
                    'foreign_schema' => ['type' => 'string', 'nullable' => true],
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add a FOREIGN KEY constraint on a table.',
                        'creation' => true,
                        'properties' => [
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
                            'name' => ['type' => 'string', 'nullable' => true],
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
            'index' => [
                'properties' => [
                    'table' => 'string',
                ],
                'changes' => [
                    'add' => [
                        'description' => 'Add an INDEX on a table.',
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
                            'name' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'rename' => [
                        'description' => 'Rename an arbitrary constraint.',
                        'creation' => false,
                        'properties' => [
                            'name' => ['type' => 'string', 'nullable' => true],
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
                            'columns' => 'array[]',
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
                            'name' => 'null|string',
                            'nulls_distinct' => ['type' => 'bool', 'default' => false],
                        ],
                    ],
                    'drop' => [
                        'description' => 'Drop a UNIQUE constraint from a table.',
                        'creation' => true,
                    ],
                ],
            ],
        ];
    }

    public function generateFromFile(): void
    {
        foreach ($this->createDefinition() as $prefix => $object) {
            $this->objectType($prefix, $object);
        }
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

    private function objectType(string $prefix, array $object)
    {
        foreach ($object['changes'] as $suffix => $change) {
            $className = $this->camelize($prefix) . $this->camelize($suffix);

            $this->objectChange($className, $object, $change);
        }
    }

    private function objectChange(string $className, array $object, array $change)
    {
        $constructorProperties = [];
        $constructorPropertiesWithDefault = [];
        $propertiesGetters = [];
        $creationString = ($change['creation'] ?? false) ? 'true' : 'false';
        $classConstants = [];

        if ($description = ($change['description'] ?? null)) {
            $description = <<<EOT
                
                /**
                 * {$description}
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
                } else {
                    $propDefault = ' = ' . $this->escape($property->default);
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
        
        declare (strict_type=1);
        
        namespace MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change;
        
        use MakinaCorpus\\QueryBuilder\\Schema\\AbstractObject;
        use MakinaCorpus\\QueryBuilder\\Schema\\Diff\\Change;
        {$description}
        class {$className} extends Change
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
                throw new \Exception("Implement me");
            }
        }
        
        EOT;

        \file_put_contents(\dirname(__DIR__) . '/' . $className . '.php', $file);
    }

    private function property(string $name, string|array $property, bool $parent = false): GeneratorProperty
    {
        if (\is_string($property)) {
            $property = ['type' => $property];
        }
        if (empty($property['type'])) {
            $property['type'] = ['mixed'];
        } else if (\is_string($property['type'])) {
            $property['type'] = \explode('|', $property['type']);
        }

        $docTypes = [];
        foreach ($property['type'] as $key => $type) {
            if (\str_ends_with($type, '[]')) {
                $type = \substr($type, 0, -2);
                $docTypes[] = 'array<' . $type . '>';
                $property['type'][$key] = 'array';
            } else {
                $docTypes[] = $type;
            }
        }

        return new GeneratorProperty(
            default: $property['default'] ?? null,
            docType: \implode('|', $docTypes),
            enumValues: $property['enum'] ?? [],
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
        public readonly null|bool|string $default = null,
        public readonly array $enumValues = [],
        public readonly bool $nullable = false,
        public readonly bool $parent = false,
    ) {}

    public function isBool(): bool
    {
        return ['bool'] === $this->types;
    }
}
