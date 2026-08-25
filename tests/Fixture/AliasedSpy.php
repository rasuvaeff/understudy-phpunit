<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

/**
 * The README's explicit composition, spelled exactly as it is published: the
 * trait's `assertPostConditions()` is aliased and called from the class's own
 * override. Without the alias PHP would resolve the conflict in favour of the
 * class and the verification would simply stop running — silently, which is
 * why the recipe exists and why it has to be executed by a test.
 *
 * @internal
 */
final class AliasedSpy extends TestCase
{
    use UnderstudyPHPUnitIntegration {
        UnderstudyPHPUnitIntegration::assertPostConditions as understudyAssertPostConditions;
    }

    public static bool $ownRan = false;

    public function runPostConditions(): void
    {
        $this->assertPostConditions();
    }

    public function declareUnmetExpectation(): void
    {
        $spy = Understudy::for(SpyContract::class);

        expect(static fn() => $spy->hit(7));
    }

    #[\Override]
    protected function assertPostConditions(): void
    {
        self::$ownRan = true;

        $this->understudyAssertPostConditions();
    }
}
