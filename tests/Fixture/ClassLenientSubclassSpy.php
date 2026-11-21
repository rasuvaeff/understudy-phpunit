<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use Rasuvaeff\Understudy\StrictStubs;

/**
 * The running class's attribute against the declaring class's: `plainMethod()`
 * is declared on the strict parent, and this class turns strictness off.
 *
 * @internal
 */
#[StrictStubs(false)]
final class ClassLenientSubclassSpy extends ClassStrictSpy
{
    #[StrictStubs]
    public function strictMethod(): void
    {
        $this->declareUnusedStub();
    }
}
