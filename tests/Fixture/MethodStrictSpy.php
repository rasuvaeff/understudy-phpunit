<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\StrictStubs;

/**
 * No class attribute and the default `understudyStrictStubs()`: only the
 * method attribute can switch strictness on.
 *
 * @internal
 */
final class MethodStrictSpy extends SpyUsingTrait
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
}
