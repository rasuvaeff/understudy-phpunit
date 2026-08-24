<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

/**
 * @internal
 */
final class ChainedSpyUsingTrait extends ChainedSpyBase
{
    use UnderstudyPHPUnitIntegration;

    public function runPostConditions(): void
    {
        $this->assertPostConditions();
    }

    public function declareUnmetExpectation(): void
    {
        $spy = Understudy::for(SpyContract::class);

        expect(static fn() => $spy->hit(7));
    }
}
