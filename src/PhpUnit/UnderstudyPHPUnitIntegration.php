<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\Exception\VerificationFailed;
use Rasuvaeff\Understudy\Understudy;

/**
 * Ends every PHPUnit test with understudy's own bookkeeping done for it.
 *
 * ```php
 * final class CheckoutTest extends TestCase
 * {
 *     use UnderstudyPHPUnitIntegration;
 *
 *     public function testChargesForTheCart(): void
 *     {
 *         $books = Understudy::for(BookRepositoryInterface::class);
 *         expect(fn () => $books->find(7))->returns($expected = new Book(7));
 *
 *         $receipt = (new Checkout($books))->charge([7]);
 *
 *         self::assertSame($expected->price, $receipt->total);
 *     }
 * }
 * ```
 *
 * One registration says both things: `find(7)` must be called exactly once,
 * and it answers `$expected`. Two rules of the engine decide that shape, and
 * this snippet used to break both:
 *
 * - **Arm before the run.** An `expect()` counts only the calls that arrive
 *   after it is declared. Written below the action it counts zero and fails
 *   as "called never" about a call that did happen; to claim a call that has
 *   already happened, use `verify()`.
 * - **One registration per call.** A `when()` stub and an `expect()` naming
 *   the same call are two registrations of one call, and the engine answers
 *   with `ConflictingExpectation`.
 *
 * Kept in step with the README's Usage section and `examples/readme-usage.php`,
 * which runs it; `DocumentedUsageTest` fails when the three drift apart.
 *
 * On a test that reached {@see TestCase::assertPostConditions()} — that is,
 * passed its body — the whole context is verified: an `expect()` the code
 * never fulfilled fails the test as an assertion failure. A test whose body
 * threw keeps its own exception untouched; verification would only mask the
 * error that actually happened. Either way the context is reset, so nothing
 * leaks into the next test.
 *
 * A base class may flip strict stubbing for a whole project by overriding
 * {@see understudyStrictStubs()}.
 *
 * If the class also overrides `assertPostConditions()` itself, PHP resolves
 * the conflict silently in favour of the class — the trait's verification
 * would stop running without any error. Compose explicitly instead:
 *
 * ```php
 * use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration {
 *     UnderstudyPHPUnitIntegration::assertPostConditions
 *         as understudyAssertPostConditions;
 * }
 *
 * protected function assertPostConditions(): void
 * {
 *     // your post-conditions ...
 *     $this->understudyAssertPostConditions();
 * }
 * ```
 *
 * The user's post-conditions run first — the check closer to the test body
 * wins, same as the README says.
 *
 * @api
 *
 * @psalm-require-extends TestCase
 */
trait UnderstudyPHPUnitIntegration
{
    /**
     * Refuses to start a test over a context some earlier test left behind —
     * that is what a broken integration looks like, and the doubles in it
     * would answer this test too.
     */
    #[Before]
    protected function understudyPrepareContext(): void
    {
        if (!Understudy::idle()) {
            throw new AssertionFailedError(
                'The current execution context still holds understudies before this test started. '
                . 'Some earlier test skipped cleanup: is the integration trait used by every '
                . 'class that creates doubles, and did an override swallow assertPostConditions()?',
            );
        }
    }

    /**
     * Drops the context unconditionally. PHPUnit does not reach
     * `assertPostConditions()` after a failing body, so this — not that
     * method — is where cleanup is guaranteed to happen.
     */
    #[After]
    protected function understudyResetContext(): void
    {
        Understudy::reset();
    }

    /**
     * The parent chain runs FIRST: a post-condition the user wrote themselves
     * is closer to the test body than this bookkeeping, so its failure is the
     * one worth reporting — and it must run at all, which it would not if an
     * unmet expectation threw ahead of it.
     */
    protected function assertPostConditions(): void
    {
        parent::assertPostConditions();

        // Verification is an assertion attempt even when it reports an unmet
        // expectation. Count it before the exception can leave this method.
        $this->addToAssertionCount(1);

        try {
            Understudy::verifyAll($this->understudyStrictStubs());
        } catch (VerificationFailed $failure) {
            throw new AssertionFailedError($failure->getMessage(), $failure->getCode(), $failure);
        }
    }

    /**
     * Whether stubs configured but never called should fail their test.
     *
     * Override in a project-wide base class to turn strictness on everywhere;
     * per-double strictness stays available through `Understudy::strict()`.
     */
    protected function understudyStrictStubs(): bool
    {
        return false;
    }
}
