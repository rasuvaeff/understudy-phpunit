<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\LeakedContext;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\Understudy;

/**
 * The class that causes the problem: it makes a double and does NOT use the
 * trait, so nothing drops the context when it ends.
 */
final class LeakingTest extends TestCase
{
    public function testMakesADoubleAndNeverCleansUp(): void
    {
        $gate = Understudy::for(Gate::class);

        self::assertTrue($gate->open(1) === false);
    }
}
