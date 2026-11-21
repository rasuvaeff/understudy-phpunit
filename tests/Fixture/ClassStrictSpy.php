<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\StrictStubs;

/**
 * Strict stubs switched on for the whole class, with one method opting out.
 * Not final: the subclass fixture turns the class level back off.
 *
 * @internal
 */
#[StrictStubs]
class ClassStrictSpy extends SpyUsingTrait
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
}
