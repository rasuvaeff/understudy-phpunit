<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;
use function Rasuvaeff\Understudy\when;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Demonstrates the trait lifecycle on a hand-driven test case: each scenario
 * walks the same methods PHPUnit would call. In a real suite you only write
 * `use UnderstudyPHPUnitIntegration;` and plain understudy code.
 */

final class DemoTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public static function make(): self
    {
        return new self('demo');
    }

    public function runPostConditions(): void
    {
        $this->assertPostConditions();
    }

    public function runReset(): void
    {
        $this->understudyResetContext();
    }

    public function passingBodyWithFulfilledExpectation(): void
    {
        $gate = Understudy::for(Gate::class);

        when(static fn() => $gate->open(1))->returns(true);

        $gate->open(1);
    }

    public function passingBodyWithUnmetExpectation(): void
    {
        $gate = Understudy::for(Gate::class);

        expect(static fn() => $gate->open(9));
    }
}

$test = DemoTest::make();

// 1. A passing body whose expectations hold passes post-conditions.
$test->passingBodyWithFulfilledExpectation();
$test->runPostConditions();

printf("1) fulfilled expectation -> post-conditions pass\n");

// 2. An unmet expectation fails the test as an assertion failure.
$test = DemoTest::make();
$test->passingBodyWithUnmetExpectation();

try {
    $test->runPostConditions();
} catch (\Throwable $failure) {
    printf(
        "2) unmet expectation     -> %s: %s\n",
        basename(str_replace('\\', '/', get_class($failure))),
        $failure->getMessage(),
    );
}

// 3. The #[After] reset drops the context either way.
$test->runReset();

printf("3) context reset         -> idle: %s\n", var_export(Understudy::idle(), true));

interface Gate
{
    public function open(int $code): bool;
}
