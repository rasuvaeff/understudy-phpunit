<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

/**
 * @api
 */
interface SpyContract
{
    public function hit(int $code): bool;
}
