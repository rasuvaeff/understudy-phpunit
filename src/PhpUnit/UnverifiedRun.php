<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit;

use PHPUnit\Framework\TestStatus\TestStatus;

/**
 * The one decision the `#[After]` hook makes before resetting: did this test
 * pass, hold doubles, and never reach the trait's verification?
 *
 * A body that failed, errored, was skipped or marked incomplete never reached
 * `assertPostConditions()` for a reason of its own, and PHPUnit already
 * reports that reason; only a success — or a status nobody set, which is what
 * a test driven by hand outside the runner has — can hide a swallowed
 * verification.
 *
 * @internal
 */
final readonly class UnverifiedRun
{
    public static function detected(bool $verified, TestStatus $status, bool $idle): bool
    {
        if ($verified || $idle) {
            return false;
        }

        return $status->isSuccess() || $status->isUnknown();
    }
}
