<?php

declare(strict_types=1);

// @see https://cs.symfony.com
// @see https://cs.symfony.com/doc/ruleSets/index.html
// @see https://cs.symfony.com/doc/config.html

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PER-CS:risky' => true,
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,

        '@PSR12' => true,
        '@PSR12:risky' => true,

        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        '@PHP5x4Migration' => true,
        '@PHP5x6Migration:risky' => true,
        '@PHP7x0Migration' => true,
        '@PHP7x0Migration:risky' => true,
        '@PHP7x1Migration' => true,
        '@PHP7x1Migration:risky' => true,
        '@PHP7x3Migration' => true,
        '@PHP7x4Migration' => true,
        '@PHP7x4Migration:risky' => true,
        '@PHP8x0Migration' => true,
        '@PHP8x0Migration:risky' => true,
        '@PHP8x2Migration' => true,
        '@PHP8x2Migration:risky' => true,
        '@PHP8x3Migration' => true,
        '@PHP8x4Migration' => true,

        '@PHPUnit10x0Migration:risky' => true,

        'concat_space' => ['spacing' => 'one'],
        'no_empty_comment' => true,
        'phpdoc_to_comment' => false,
        'return_to_yield_from' => true,

        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    ])
    ->setFinder($finder)
;
