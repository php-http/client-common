<?php

$config = PhpCsFixer\Config::create();
$config->setRules([
    '@PSR2' => true,
    '@Symfony' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
]);

$finder = PhpCsFixer\Finder::create();
$finder->in([
    'src',
    'spec'
]);

$config->setFinder($finder);

return $config;
