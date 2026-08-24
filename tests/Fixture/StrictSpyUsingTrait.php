<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

/**
 * @internal
 */
final class StrictSpyUsingTrait extends SpyUsingTrait
{
    #[\Override]
    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}
