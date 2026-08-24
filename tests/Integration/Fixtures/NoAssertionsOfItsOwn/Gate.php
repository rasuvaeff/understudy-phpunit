<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\NoAssertionsOfItsOwn;

/**
 * @internal
 */
interface Gate
{
    public function open(int $code): bool;
}
