<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

$config = new PhpCsFixer\Config();
return $config->setRules(
    [
        '@PSR12' => true,
        'elseif' => false,
        'array_syntax' => ['syntax' => 'short'],
        /*
        'braces_position' => [
            'allow_single_line_anonymous_functions' => true,
            'allow_single_line_empty_anonymous_classes' => true,
        ],
         */
        'braces_position' => false, // Breaks empty {} with a newline.
        'method_argument_space' => false, // Breaks lots of formatting.
        'single_line_empty_body' => false,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
