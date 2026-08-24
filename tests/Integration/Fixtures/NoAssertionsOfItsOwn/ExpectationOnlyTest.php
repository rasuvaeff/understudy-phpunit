<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\NoAssertionsOfItsOwn;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

/**
 * A test whose only check is an understudy expectation. PHPUnit is strict
 * about tests that assert nothing, and this one must not trip it: the trait
 * counts the verification before the runner reaches its verdict.
 *
 * The Testo adapter cannot do the same — it is outer to that decision — which
 * is why it has to take the `Risky` verdict back instead. Same claim, two
 * runners, and this is where the PHPUnit half is checked.
 *
 * @internal
 */
final class ExpectationOnlyTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testBodyAssertsNothingItself(): void
    {
        $gate = Understudy::for(Gate::class);

        expect(static fn() => $gate->open(7));

        $gate->open(7);
    }
}
