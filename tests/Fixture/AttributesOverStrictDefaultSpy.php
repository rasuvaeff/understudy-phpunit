<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\StrictStubs;

/**
 * `understudyStrictStubs()` says strict, the class attribute says lenient,
 * and one method says strict again: each nearer scope wins over the wider one.
 *
 * @internal
 */
#[StrictStubs(false)]
final class AttributesOverStrictDefaultSpy extends SpyUsingTrait
{
    public function plainMethod(): void
    {
        $this->declareUnusedStub();
    }

    #[StrictStubs]
    public function strictMethod(): void
    {
        $this->declareUnusedStub();
    }

    #[\Override]
    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}
