<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;
use function Rasuvaeff\Understudy\when;

/**
 * The trait wired the way a user's test class uses it, with public handles so
 * a Testo-driven unit test can walk the lifecycle by hand. Not final on
 * purpose: the strict-stubs fixture specialises it.
 */
class SpyUsingTrait extends TestCase
{
    use \Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;

    public function runGuard(): void
    {
        $this->understudyPrepareContext();
    }

    public function runReset(): void
    {
        $this->understudyResetContext();
    }

    public function runPostConditions(): void
    {
        $this->assertPostConditions();
    }

    public function declareSatisfiedExpectation(): void
    {
        $spy = Understudy::for(SpyContract::class);

        when(static fn() => $spy->hit(7))->returns(true);

        $spy->hit(7);
    }

    public function declareUnmetExpectation(): void
    {
        $spy = Understudy::for(SpyContract::class);

        expect(static fn() => $spy->hit(7));
    }

    public function declareUnusedStub(): void
    {
        $spy = Understudy::for(SpyContract::class);

        when(static fn() => $spy->hit(7))->returns(true);
    }
}
