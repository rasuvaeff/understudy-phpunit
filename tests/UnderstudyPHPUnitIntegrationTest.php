<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests;

use PHPUnit\Framework\AssertionFailedError;
use Rasuvaeff\Understudy\Exception\VerificationFailed;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\ChainedSpyBase;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\ChainedSpyUsingTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyContract;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyUsingTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyWithoutTrait;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\StrictSpyUsingTrait;
use Rasuvaeff\Understudy\Understudy;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(\Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration::class)]
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

        // The trait does not verify twice: post-conditions threw before the
        // count was touched, and the context is still dropped by hand here.
        Understudy::reset();
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
            Assert::string($failure->getMessage())
                ->contains('still holds understudies')
                ->contains('is the integration trait used by every class that creates doubles')
                ->contains('swallow assertPostConditions()?');
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

        $test->runReset();

        Assert::true(Understudy::idle());
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
