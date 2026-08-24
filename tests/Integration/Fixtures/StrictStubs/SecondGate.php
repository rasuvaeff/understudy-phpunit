<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\StrictStubs;

/**
 * @internal
 */
interface SecondGate
{
    public function open(int $code): bool;
}
