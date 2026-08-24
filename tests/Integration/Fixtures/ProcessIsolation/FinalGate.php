<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration\Fixtures\ProcessIsolation;

final class FinalGate
{
    public function open(): bool
    {
        return true;
    }
}
