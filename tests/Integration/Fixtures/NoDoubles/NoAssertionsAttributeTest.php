<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\NoDoubles;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;

/**
 * The attribute goes on tests of the form "this should simply not throw" —
 * boot, migrations, initialization — and such a class inherits the trait
 * along with everything else in a project-wide base class.
 *
 * The trait used to count an assertion unconditionally, so the test reported
 * "performed 1 assertion" and went risky, for a reason that was the adapter's
 * and not the test's.
 *
 * @internal
 */
final class NoAssertionsAttributeTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    #[DoesNotPerformAssertions]
    public function testBootsWithoutAsserting(): void
    {
        // Nothing. That is the point of the attribute.
    }
}
