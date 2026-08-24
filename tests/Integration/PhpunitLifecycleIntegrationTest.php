<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Integration;

use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

/**
 * Drives real PHPUnit processes over fixture projects, so the lifecycle is
 * proven where it actually runs: PHPUnit decides when post-conditions happen,
 * and the runner's own output is the observable.
 */
#[Test]
#[CoversNothing]
final class PhpunitLifecycleIntegrationTest
{
    public function unmetExpectationFailsAPassingBody(): void
    {
        [$exit, $output] = $this->runPhpunit('UnmetExpectation');

        Assert::same($exit, 1);
        Assert::string($output)
            ->contains('expected')
            ->contains('open(7)')
            ->contains('never');
        Assert::same($this->summaryCount($output, 'Failures'), 1);
    }

    public function originalFailureWinsOverVerification(): void
    {
        [$exit, $output] = $this->runPhpunit('OriginalFailure');

        Assert::same($exit, 2);
        // The body's own exception is what gets reported; verification never
        // ran, and its unmet expectation must not appear anywhere.
        Assert::string($output)->contains('the code under test broke');
        Assert::false(str_contains($output, 'open(7)'));
        Assert::same($this->summaryCount($output, 'Errors'), 1);
    }

    public function strictStubsBaseClassFailsOnlyTheUnusedStub(): void
    {
        [$exit, $output] = $this->runPhpunit('StrictStubs');

        Assert::same($exit, 1);
        Assert::string($output)->contains('never used');
        // The used stub passes; only the configured-but-unused one fails.
        Assert::same($this->summaryCount($output, 'Failures'), 1);
        Assert::string($output)->contains('Tests: 2');
    }

    public function bypassFinalsSurvivesProcessIsolation(): void
    {
        [$exit, $output] = $this->runPhpunit('ProcessIsolation');

        Assert::same($exit, 0);
        // The body asserts once and the trait's verification counts once —
        // the exact total is what pins `addToAssertionCount(1)`.
        Assert::string($output)->contains('OK (1 test, 2 assertions)');
    }

    public function expectationOnlyTestIsNotRiskyForNotAsserting(): void
    {
        [$exit, $output] = $this->runPhpunit('NoAssertionsOfItsOwn');

        // The control test carries no trait and asserts nothing, so PHPUnit's
        // strictness must catch it — that is what proves the setting is armed.
        Assert::same($this->summaryCount($output, 'Risky'), 1);
        Assert::string($output)->contains('ControlTest::testAssertsNothingAndHasNoTrait');
        // And the expectation-only test must not be among them: the trait's
        // assertion lands before the verdict.
        Assert::false(str_contains($output, 'ExpectationOnlyTest::testBodyAssertsNothingItself'));
        Assert::string($output)->contains('Tests: 2, Assertions: 1, Risky: 1');
        Assert::same($exit, 0);
    }

    /**
     * @return array{int, string}
     */
    private function runPhpunit(string $fixture): array
    {
        $root = dirname(__DIR__, 2);
        $config = __DIR__ . '/Fixtures/' . $fixture . '/phpunit.xml';

        $command = sprintf(
            '%s %s -c %s --no-progress 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($root . '/vendor/bin/phpunit'),
            escapeshellarg($config),
        );

        exec($command, $lines, $exit);

        return [$exit, implode("\n", $lines)];
    }

    private function summaryCount(string $output, string $kind): int
    {
        return preg_match('/' . $kind . ': (\d+)/', $output, $m) === 1 ? (int) $m[1] : 0;
    }
}
