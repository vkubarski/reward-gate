<?php

$finder = PhpCsFixer\Finder::create()
        ->in(__DIR__ . '/src')
        ->in(__DIR__ . '/public')
        ->in(__DIR__ . '/tests')
        ->name('*.php');

return (new PhpCsFixer\Config())
        ->setRules([
                '@PSR12' => true,
        ])
        ->setFinder($finder);
