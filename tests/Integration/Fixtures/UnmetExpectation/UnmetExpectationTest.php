<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\UnmetExpectation;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

final class UnmetExpectationTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testBodyPassesButTheExpectationIsNeverFulfilled(): void
    {
        $spy = Understudy::for(Gate::class);

        expect(static fn() => $spy->open(7));
    }
}
