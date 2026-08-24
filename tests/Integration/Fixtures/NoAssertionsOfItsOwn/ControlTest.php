<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\NoAssertionsOfItsOwn;

use PHPUnit\Framework\TestCase;

/**
 * The control group: a test that exercises something and forgets to assert on
 * it — the mistake PHPUnit's strictness exists to catch. It proves the strict
 * setting in `phpunit.xml` really is armed, so the other file passing means
 * something.
 *
 * @internal
 */
final class ControlTest extends TestCase
{
    public function testAssertsNothingAndHasNoTrait(): void
    {
        $gate = new class implements Gate {
            #[\Override]
            public function open(int $code): bool
            {
                return $code > 0;
            }
        };

        $gate->open(7);
    }
}
