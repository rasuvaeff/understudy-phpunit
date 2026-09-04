<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\LeakedContext;

interface Gate
{
    public function open(int $code): bool;
}
