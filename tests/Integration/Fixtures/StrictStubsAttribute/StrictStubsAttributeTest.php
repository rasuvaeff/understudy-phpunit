<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\StrictStubsAttribute;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\StrictStubs;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\when;

/**
 * The attribute read under a real runner: the method name the trait passes
 * on is PHPUnit's own, and for a data-provided test it must still name the
 * method, not the method plus its data set.
 */
#[StrictStubs]
final class StrictStubsAttributeTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public static function codes(): iterable
    {
        yield 'first' => [1];
        yield 'second' => [2];
    }

    public function testTheClassAttributeFailsAnUnusedStub(): void
    {
        $spy = Understudy::for(Gate::class);

        when(static fn() => $spy->open(7))->returns(true);
    }

    #[StrictStubs(false)]
    public function testTheMethodAttributeToleratesAnUnusedStub(): void
    {
        $spy = Understudy::for(Gate::class);

        when(static fn() => $spy->open(7))->returns(true);

        self::assertTrue(true);
    }

    #[DataProvider('codes')]
    #[StrictStubs(false)]
    public function testTheMethodAttributeHoldsForEveryDataSet(int $code): void
    {
        $spy = Understudy::for(Gate::class);

        when(static fn() => $spy->open($code))->returns(true);

        self::assertTrue(true);
    }
}
