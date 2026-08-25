<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\FinderConfig;
use Testo\Application\Config\SuiteConfig;

return new ApplicationConfig(
    src: ['src'],
    suites: [
        new SuiteConfig(
            name: 'Unit',
            // tests/Integration spawns real PHPUnit processes on fixture
            // projects; those run as their own suite.
            location: new FinderConfig(include: ['tests'], exclude: ['tests/Integration']),
        ),
        new SuiteConfig(
            name: 'Integration',
            // The fixture projects are PHPUnit and Pest projects, not Testo
            // ones, and the Pest one carries a vendor tree of its own: let
            // the finder walk into it and it redeclares half of PHPUnit.
            location: new FinderConfig(
                include: ['tests/Integration'],
                exclude: ['tests/Integration/Fixtures'],
            ),
        ),
        new SuiteConfig(
            name: 'Benchmarks',
            location: ['benchmarks'],
        ),
    ],
);
