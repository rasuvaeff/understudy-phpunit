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
     * Whether this test's post-conditions reached the verification. Cleared by
     * the `#[Before]` hook, set by {@see assertPostConditions()}, read by the
     * `#[After]` hook — the one place that can tell a verification that never
     * ran from one that found nothing to report.
     */
    private bool $understudyVerified = false;

    /**
     * Refuses to start a test over a context some earlier test left behind —
     * that is what a broken integration looks like, and the doubles in it
     * would answer this test too.
     *
     * `protected` because PHPUnit discovers hook methods by attribute and a
     * trait cannot keep them private from the class using it — not because a
     * subclass is meant to override it. The two hooks are the mechanism, not
     * the contract: only {@see understudyStrictStubs()} is an override point.
     *
     * @internal
     */
    #[Before]
    protected function understudyPrepareContext(): void
    {
        $this->understudyVerified = false;

        if (!Understudy::idle()) {
            throw new AssertionFailedError(
                'The current execution context still holds understudies before this test started. '
                . 'A double created in setUpBeforeClass() lands here, and the context lives for one '
                . 'test — create it in setUp() instead. Otherwise some earlier test skipped cleanup: '
                . 'is the integration trait used by every class that creates doubles, and did an '
                . 'override swallow assertPostConditions()?',
            );
        }
    }

    /**
     * Drops the context unconditionally. PHPUnit does not reach
     * `assertPostConditions()` after a failing body, so this — not that
     * method — is where cleanup is guaranteed to happen.
     *
     * Before dropping it, one check: a body that passed, held doubles, and
     * whose post-conditions never reached the verification is a class that
     * overrides `assertPostConditions()` without composing the trait's. That
     * used to be silent — the reset ran, the next test's guard found a clean
     * context, and every `expect()` in the class was green forever. An
     * `AssertionFailedError` from an `#[After]` hook is a failure of the test,
     * which is what an unverified expectation is.
     *
     * @internal see {@see understudyPrepareContext()} for why it is protected
     */
    #[After]
    protected function understudyResetContext(): void
    {
        try {
            if (UnverifiedRun::detected($this->understudyVerified, $this->status(), Understudy::idle())) {
                throw new AssertionFailedError(
                    'This test created understudies and passed, but its expectations were never verified: '
                    . 'assertPostConditions() did not reach the trait. The class overrides assertPostConditions() '
                    . 'without composing the trait\'s — alias it (UnderstudyPHPUnitIntegration::assertPostConditions '
                    . 'as understudyAssertPostConditions) and call the alias from your own, as the README shows.',
                );
            }
        } finally {
            Understudy::reset();
        }
    }

    /**
     * The parent chain runs FIRST: a post-condition the user wrote themselves
     * is closer to the test body than this bookkeeping, so its failure is the
     * one worth reporting — and it must run at all, which it would not if an
     * unmet expectation threw ahead of it.
     *
     * @internal PHPUnit hook; compose through the documented alias when the
     * using test class also defines its own post-conditions.
     */
    protected function assertPostConditions(): void
    {
        parent::assertPostConditions();

        $this->understudyVerified = true;

        // A test that created no double asked understudy nothing, and there is
        // no assertion attempt here to count. Counting one anyway made
        // `#[DoesNotPerformAssertions]` report "performed 1 assertion" and turn
        // the test risky — on exactly the tests that attribute is written for,
        // in a class that inherits this trait along with everything else.
        if (Understudy::idle()) {
            return;
        }

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
     * Override in a project-wide base class to turn strictness on everywhere.
     * There is no per-double form: `Understudy::strict()` is strict dispatch —
     * "fail on any call no expectation matched" — and says nothing about a stub
     * that was configured and never called. That is `when(…)->times(n)`.
     */
    protected function understudyStrictStubs(): bool
    {
        return false;
    }
}
