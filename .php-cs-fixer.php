<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/local/modules',
        __DIR__ . '/local/components',
        __DIR__ . '/local/php_interface',
        __DIR__ . '/local/exchange',
        __DIR__ . '/local/agents',
        __DIR__ . '/local/migrations',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_whitespace_in_blank_line' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false);
