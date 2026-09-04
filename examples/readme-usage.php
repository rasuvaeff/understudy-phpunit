<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/_check.php';

/**
 * The README's Usage example, executable.
 *
 * It is here because the README's version was wrong for two releases and
 * nothing noticed: it stubbed `find(7)` with `when()` and then claimed the
 * same call with `expect()`, which the engine refuses with
 * `ConflictingExpectation`, and it wrote the `expect()` below the action,
 * where it would have counted zero. A snippet nobody runs is a snippet that
 * drifts, so this one runs.
 */

final class Book
{
    public function __construct(public int $id, public int $price = 100) {}
}

interface BookRepositoryInterface
{
    public function find(int $id): ?Book;
}

final class Checkout
{
    public function __construct(private BookRepositoryInterface $books) {}

    /** @param list<int> $cart */
    public function charge(array $cart): Book
    {
        $book = $this->books->find($cart[0]);
        \assert($book !== null);

        return $book;
    }
}

final class CheckoutTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public static function make(): self
    {
        return new self('demo');
    }

    public function body(): void
    {
        $books = Understudy::for(BookRepositoryInterface::class);
        expect(fn() => $books->find(7))->returns($expected = new Book(7));

        $receipt = (new Checkout($books))->charge([7]);

        check($receipt->price === $expected->price, 'the subject got the stubbed book');
    }

    public function bodyThatNeverCalls(): void
    {
        $books = Understudy::for(BookRepositoryInterface::class);
        expect(fn() => $books->find(7))->returns(new Book(7));
    }

    public function runPostConditions(): void
    {
        $this->assertPostConditions();
    }

    public function runReset(): void
    {
        $this->understudyResetContext();
    }
}

// 1. One registration says both things, and the adapter verifies it.
$test = CheckoutTest::make();
$test->body();
$test->runPostConditions();
check(true, 'a fulfilled expectation passes post-conditions');
$test->runReset();

// 2. The claim is real: a subject that never calls fails the test.
$test = CheckoutTest::make();
$test->bodyThatNeverCalls();

$failed = false;

try {
    $test->runPostConditions();
} catch (Throwable $failure) {
    $failed = true;
}
check($failed, 'an unmet expectation fails the test rather than passing green');
$test->runReset();

// 3. The pair the README used to show is refused at registration — from engine
//    0.3.0 on. This package allows ^0.1 || ^0.2 || ^0.3 || ^0.4 || ^0.5 || ^0.6
//    || ^0.7, and on the older two the pair degraded silently instead, so the
//    claim is made only where the engine can hold it. Without this guard the
//    `prefer-lowest` CI job fails on a version difference rather than on a defect.
if (class_exists(\Rasuvaeff\Understudy\Exception\ConflictingExpectation::class)) {
    $books = Understudy::for(BookRepositoryInterface::class);
    \Rasuvaeff\Understudy\when(fn() => $books->find(7))->returns(new Book(7));

    $refused = false;

    try {
        expect(fn() => $books->find(7));
    } catch (\Rasuvaeff\Understudy\Exception\ConflictingExpectation) {
        $refused = true;
    }

    check($refused, 'when() plus expect() for the same call is refused, not silently merged');
} else {
    printf("  --  refusal claim skipped: this engine predates ConflictingExpectation (0.3.0)\n");
}

Understudy::reset();
