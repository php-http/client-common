<?php

$config = PhpCsFixer\Config::create();
$config->setRules([
    '@PSR2' => true,
    '@Symfony' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'no_empty_phpdoc' => true,
    'no_superfluous_phpdoc_tags' => true,
]);

$finder = PhpCsFixer\Finder::create();
$finder->in([
    'src',
    'spec'
]);

$config->setFinder($finder);

return $config;
