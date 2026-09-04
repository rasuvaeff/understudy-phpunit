<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\LeakedContext;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;

/**
 * The class that pays for it. Its body is blameless; the `#[Before]` guard
 * refuses to start over the context the class before it left behind.
 */
final class GuardedTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testStartsOverSomebodyElsesLeftovers(): void
    {
        self::assertTrue(true);
    }
}
