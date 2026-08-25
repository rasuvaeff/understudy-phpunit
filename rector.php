<?php

declare(strict_types=1);

use Rasuvaeff\RectorNamedLiterals\AddNameToLiteralArgumentRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // The Pest recipe fixture is a Composer project of its own: its vendor
    // tree would take rector minutes to walk, and its test files are the
    // README's code, kept verbatim on purpose.
    ->withSkip([__DIR__ . '/tests/Integration/Fixtures/Pest'])
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withRules([AddNameToLiteralArgumentRector::class]);
