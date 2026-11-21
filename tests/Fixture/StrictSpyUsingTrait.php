<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\StrictStubs;

/**
 * @internal
 */
final class StrictSpyUsingTrait extends SpyUsingTrait
{
    public function plainMethod(): void
    {
        $this->declareUnusedStub();
    }

    #[StrictStubs(false)]
    public function lenientMethod(): void
    {
        $this->declareUnusedStub();
    }

    #[\Override]
    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}
