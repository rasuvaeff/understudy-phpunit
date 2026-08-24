<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\ProcessIsolation;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\when;

final class BypassFinalTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testDoublesAFinalClassInAnIsolatedProcess(): void
    {
        $gate = Understudy::for(FinalGate::class);

        when(static fn() => $gate->open())->returns(false);

        self::assertFalse($gate->open());
    }
}
