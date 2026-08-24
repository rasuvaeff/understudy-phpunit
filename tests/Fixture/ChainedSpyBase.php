<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

use PHPUnit\Framework\TestCase;

/**
 * An intermediate base class, so the trait's `parent::assertPostConditions()`
 * has something of the user's own to reach.
 *
 * @internal
 */
class ChainedSpyBase extends TestCase
{
    public static bool $parentRan = false;

    #[\Override]
    protected function assertPostConditions(): void
    {
        self::$parentRan = true;

        parent::assertPostConditions();
    }
}
