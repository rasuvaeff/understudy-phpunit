<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\StrictStubs;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\when;

abstract class StrictBase extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    // Overriding a trait-provided method: `#[\Override]` would fatal here,
    // since PHP resolves it against parents and interfaces only.
    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}

final class StrictStubsTest extends StrictBase
{
    public function testAConfiguredButUnusedStubFails(): void
    {
        $spy = Understudy::for(Gate::class);

        when(static fn() => $spy->open(7))->returns(true);
    }

    public function testAUsedStubPasses(): void
    {
        $spy = Understudy::for(SecondGate::class);

        when(static fn() => $spy->open(1))->returns(true);

        self::assertTrue($spy->open(1));
    }
}
