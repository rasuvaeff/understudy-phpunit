<?php

declare(strict_types=1);

// The fixture bootstrap: bypass must be enabled before the final class is
// first loaded, and under process isolation every test runs in its own fresh
// process — so this file runs once per test, exactly as a user's bootstrap
// would.
require __DIR__ . '/../../../../vendor/autoload.php';

\Rasuvaeff\Understudy\Understudy::bypassFinals(
    Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\ProcessIsolation\FinalGate::class,
);
