<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Benchmarks;

use function Rasuvaeff\Understudy\expect;
use function Rasuvaeff\Understudy\when;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyContract;
use Rasuvaeff\Understudy\PhpUnit\Tests\Fixture\SpyUsingTrait;
use Rasuvaeff\Understudy\Understudy;
use Testo\Bench;

/**
 * What the trait charges for ending a test automatically, measured against
 * what the code would have to do by hand: a bare post-conditions chain for
 * the wrapper itself, and a manual `reset()` for the lifecycle around real
 * doubles — because a suite that skips verification still cannot let one
 * test's doubles leak into the next.
 */
final class UnderstudyPHPUnitIntegrationBench
{
    private static ?SpyUsingTrait $test = null;

    private static ?BareTestCase $bare = null;

    // --- Wrapping post-conditions -------------------------------------------

    #[Bench(['bare TestCase' => [self::class, 'barePostConditions']], calls: 10_000)]
    public static function passWithoutDoubles(): void
    {
        self::test()->runPostConditions();
    }

    public static function barePostConditions(): void
    {
        (self::$bare ??= new BareTestCase('bench'))->runPostConditions();
    }

    // --- Full lifecycle around a real double ---------------------------------

    #[Bench(['reset only' => [self::class, 'fulfilledWithManualReset']], calls: 10_000)]
    public static function passWithFulfilledExpectation(): void
    {
        self::test()->declareSatisfiedExpectation();
        self::test()->runPostConditions();
        Understudy::reset();
    }

    public static function fulfilledWithManualReset(): void
    {
        $spy = Understudy::for(SpyContract::class);

        when(static fn () => $spy->hit(7))->returns(true);

        $spy->hit(7);

        Understudy::reset();
    }

    // --- Verification that fails after a passing body ------------------------

    #[Bench(['reset only' => [self::class, 'unmetWithManualReset']], calls: 1_000)]
    public static function unmetExpectationFailsAfterTheFact(): void
    {
        self::test()->declareUnmetExpectation();

        try {
            self::test()->runPostConditions();
        } catch (\PHPUnit\Framework\AssertionFailedError) {
            // expected: this scenario prices the failing report
        }

        Understudy::reset();
    }

    public static function unmetWithManualReset(): void
    {
        $spy = Understudy::for(SpyContract::class);

        expect(static fn () => $spy->hit(9));

        Understudy::reset();
    }

    private static function test(): SpyUsingTrait
    {
        return self::$test ??= new SpyUsingTrait('bench');
    }
}

/**
 * @internal the baseline every scenario above is compared against
 */
final class BareTestCase extends TestCase
{
    public function runPostConditions(): void
    {
        parent::assertPostConditions();
    }
}
