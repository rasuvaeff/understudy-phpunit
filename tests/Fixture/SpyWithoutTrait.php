<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

/**
 * @internal
 */
final class SpyWithoutTrait extends TestCase
{
    public function declareUnmetExpectation(): void
    {
        $spy = Understudy::for(SpyContract::class);

        expect(static fn() => $spy->hit(7));
    }
}
