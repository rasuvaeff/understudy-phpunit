<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestStatus\TestStatus;
use Rasuvaeff\Understudy\Exception\VerificationFailed;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\AliasedSpy;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\ChainedSpyBase;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\ChainedSpyUsingTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyContract;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyUsingTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyWithoutTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\StrictSpyUsingTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SwallowingSpy;
use Rasuvaeff\Understudy\PhpUnit\UnverifiedRun;
use Rasuvaeff\Understudy\Understudy;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(\Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration::class)]
#[Covers(UnverifiedRun::class)]
final class UnderstudyPHPUnitIntegrationTest
{
    #[BeforeTest]
    public function cleanContext(): void
    {
        Understudy::reset();
    }

    public function satisfiedExpectationPassesPostConditions(): void
    {
        $test = new SpyUsingTrait('probe');
        $test->declareSatisfiedExpectation();

        $test->runPostConditions();

        // Verification checked the context; the double itself leaves it only
        // with the #[After] reset.
        $test->runReset();

        Assert::true(Understudy::idle());
    }

    /**
     * The hole the `#[Before]` guard cannot see: a class that overrides
     * `assertPostConditions()` without composing the trait's skips the
     * verification, and the `#[After]` reset then hands the next test a clean
     * context. The reset hook now notices that a passing body with doubles
     * was never verified and fails the test — and still resets.
     */
    public function aSwallowedPostConditionFailsTheTestInsteadOfPassingSilently(): void
    {
        $test = new SwallowingSpy('probe');
        $test->runGuard();
        $test->declareUnmetExpectation();
        $test->runPostConditions();

        Assert::true($test->ownPostConditionsRan);

        try {
            $test->runReset();

            Assert::fail('Expected the reset hook to report the unverified expectation');
        } catch (AssertionFailedError $failure) {
            Assert::same(
                $failure->getMessage(),
                'This test created understudies and passed, but its expectations were never verified: '
                . 'assertPostConditions() did not reach the trait. The class overrides assertPostConditions() '
                . 'without composing the trait\'s — alias it (UnderstudyPHPUnitIntegration::assertPostConditions '
                . 'as understudyAssertPostConditions) and call the alias from your own, as the README shows.',
            );
        }

        Assert::true(Understudy::idle());
    }

    /**
     * The same reset hook stays quiet on every legitimate path: no doubles at
     * all, doubles whose verification did run, and — with the guard cleared
     * per test — a second test after a verified one.
     */
    public function theResetHookStaysQuietWhenVerificationRan(): void
    {
        $test = new SpyUsingTrait('probe');
        $test->runGuard();
        $test->declareSatisfiedExpectation();
        $test->runPostConditions();
        $test->runReset();

        Assert::true(Understudy::idle());

        $test->runGuard();
        $test->runReset();

        Assert::true(Understudy::idle());
    }

    /**
     * The flag is per test, not per instance: a verified first test must not
     * vouch for a second one whose post-conditions were swallowed.
     */
    public function theGuardIsClearedForEveryTest(): void
    {
        $test = new SwallowingSpy('probe');
        $test->runGuard();
        $test->declareSatisfiedExpectation();
        $test->understudyAssertPostConditionsForTheTest();
        $test->runReset();

        $test->runGuard();
        $test->declareUnmetExpectation();
        $test->runPostConditions();

        Expect::exception(AssertionFailedError::class)->withMessageContaining('never verified');

        $test->runReset();
    }

    #[DataProvider('unverifiedRunProvider')]
    public function theResetHookDecidesFromTheFlagTheStatusAndTheContext(bool $verified, TestStatus $status, bool $idle, bool $detected): void
    {
        Assert::same(UnverifiedRun::detected($verified, $status, $idle), $detected);
    }

    public static function unverifiedRunProvider(): iterable
    {
        yield 'passed, doubles, never verified' => [false, TestStatus::success(), false, true];
        yield 'driven by hand, doubles, never verified' => [false, TestStatus::unknown(), false, true];
        yield 'verified' => [true, TestStatus::success(), false, false];
        yield 'no doubles' => [false, TestStatus::success(), true, false];
        yield 'body failed' => [false, TestStatus::failure('x'), false, false];
        yield 'body errored' => [false, TestStatus::error('x'), false, false];
        yield 'body skipped' => [false, TestStatus::skipped('x'), false, false];
        yield 'body incomplete' => [false, TestStatus::incomplete('x'), false, false];
    }

    public function allTraitHooksAreInternal(): void
    {
        $reflection = new \ReflectionClass(\Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration::class);

        foreach (['understudyPrepareContext', 'understudyResetContext', 'assertPostConditions'] as $method) {
            $docblock = $reflection->getMethod($method)->getDocComment();

            Assert::true($docblock !== false && str_contains($docblock, '@internal'));
        }
    }

    public function unmetExpectationFailsAsAnAssertionFailure(): void
    {
        $test = new SpyUsingTrait('probe');
        $test->declareUnmetExpectation();

        try {
            $test->runPostConditions();

            Assert::fail('Expected an AssertionFailedError for the unmet expectation');
        } catch (AssertionFailedError $failure) {
            Assert::string($failure->getMessage())
                ->contains('expected')
                ->contains('hit(')
                ->contains('never');
            Assert::instanceOf($failure->getPrevious(), VerificationFailed::class);
        }

        // The failed verification is still one assertion attempt; the
        // context is dropped by hand here because this unit fixture bypasses
        // PHPUnit's lifecycle attributes.
        Assert::same($test->numberOfAssertionsPerformed(), 1);

        Understudy::reset();
    }

    /**
     * A test that created no double asked understudy nothing, so there is no
     * assertion attempt here to count. Counting one anyway made
     * `#[DoesNotPerformAssertions]` report "performed 1 assertion" and turn
     * the test risky, for a reason that was the adapter's and not the test's.
     */
    public function postConditionsCountNothingWithoutADouble(): void
    {
        $test = new SpyUsingTrait('probe');

        $test->runPostConditions();

        Assert::same($test->numberOfAssertionsPerformed(), 0);
    }

    public function strictStubsDefaultToleratesAnUnusedStub(): void
    {
        $test = new SpyUsingTrait('probe');
        $test->declareUnusedStub();

        $test->runPostConditions();

        Assert::false(Understudy::idle());

        $test->runReset();

        Assert::true(Understudy::idle());
    }

    public function strictStubsOverrideFailsAnUnusedStub(): void
    {
        $test = new StrictSpyUsingTrait('probe');
        $test->declareUnusedStub();

        try {
            $test->runPostConditions();

            Assert::fail('Expected an AssertionFailedError for the unused stub');
        } catch (AssertionFailedError $failure) {
            Assert::string($failure->getMessage())->contains('never used');
        }

        Understudy::reset();
    }

    public function guardRefusesToStartOverALeakedContext(): void
    {
        Understudy::for(SpyContract::class);

        $test = new SpyUsingTrait('probe');

        try {
            $test->runGuard();

            Assert::fail('Expected the integration guard to fire over a leaked context');
        } catch (AssertionFailedError $failure) {
            // The full diagnostic survives only when every message operand
            // concatenates in order.
            // Asserted whole: it is the only thing the reader gets, and every
            // half of a concatenation in one is a mutant a `contains()` cannot
            // see. The first branch names the cause the old message did not —
            // `#[Before]` runs after `setUpBeforeClass()`, so a double created
            // there was blamed on an earlier test that never ran.
            Assert::same(
                $failure->getMessage(),
                'The current execution context still holds understudies before this test started. '
                . 'A double created in setUpBeforeClass() lands here, and the context lives for one '
                . 'test — create it in setUp() instead. Otherwise some earlier test skipped cleanup: '
                . 'is the integration trait used by every class that creates doubles, and did an '
                . 'override swallow assertPostConditions()?',
            );
        }
    }

    public function guardAcceptsACleanContext(): void
    {
        $test = new SpyUsingTrait('probe');

        $test->runGuard();

        Assert::true(Understudy::idle());
    }

    public function resetDropsTheContextAfterTheTest(): void
    {
        $test = new SpyUsingTrait('probe');
        $test->declareSatisfiedExpectation();
        $test->runPostConditions();

        $test->runReset();

        Assert::true(Understudy::idle());
    }

    public function theAliasedCompositionFromTheReadmeVerifies(): void
    {
        // The recipe for a class that overrides post-conditions itself. Both
        // halves have to happen: the user's own code, and the verification
        // the alias keeps reachable.
        $test = new AliasedSpy('probe');
        $test->declareUnmetExpectation();

        AliasedSpy::$ownRan = false;

        try {
            $test->runPostConditions();

            Assert::fail('Expected an AssertionFailedError for the unmet expectation');
        } catch (AssertionFailedError $error) {
            Assert::instanceOf($error->getPrevious(), VerificationFailed::class);
        }

        Assert::true(AliasedSpy::$ownRan);
    }

    public function postConditionsReachTheParentChain(): void
    {
        // `parent::assertPostConditions()` inside the trait must land on the
        // using class's own parent, so a base class's post-conditions still run.
        $test = new ChainedSpyUsingTrait('probe');

        ChainedSpyBase::$parentRan = false;

        $test->runPostConditions();

        Assert::true(ChainedSpyBase::$parentRan);
    }

    public function parentChainRunsEvenWhenVerificationFails(): void
    {
        // The user's own post-conditions must not be skipped by an unmet
        // expectation: they run ahead of the bookkeeping, not behind it.
        $test = new ChainedSpyUsingTrait('probe');
        $test->declareUnmetExpectation();

        ChainedSpyBase::$parentRan = false;

        try {
            $test->runPostConditions();

            Assert::fail('Expected an AssertionFailedError for the unmet expectation');
        } catch (AssertionFailedError $failure) {
            Assert::string($failure->getMessage())->contains('hit(');
        }

        Assert::true(ChainedSpyBase::$parentRan);

        Understudy::reset();
    }

    public function verificationCountsAsOneAssertion(): void
    {
        // PHPUnit renamed the getter between supported majors, so this reads
        // the number the runner itself reports rather than a version-specific
        // accessor of the count.
        $test = new SpyUsingTrait('probe');
        $test->declareSatisfiedExpectation();

        $before = $test->numberOfAssertionsPerformed();

        $test->runPostConditions();

        Assert::same($test->numberOfAssertionsPerformed() - $before, 1);

        $test->runReset();
    }

    public function aClassWithoutTheTraitLeavesItsDoublesUnverified(): void
    {
        // The control group: without the trait, an unmet expectation is nobody's
        // problem — which is exactly the gap the trait exists to close.
        $test = new SpyWithoutTrait('probe');
        $test->declareUnmetExpectation();

        Assert::false(Understudy::idle());

        Understudy::reset();
    }
}
