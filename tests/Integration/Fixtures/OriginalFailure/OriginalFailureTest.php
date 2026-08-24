<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\OriginalFailure;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

final class OriginalFailureTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testTheBodyThrowsAndAnExpectationIsAlsoUnmet(): void
    {
        $spy = Understudy::for(Gate::class);

        expect(static fn() => $spy->open(7));

        throw new \RuntimeException('the code under test broke');
    }
}
