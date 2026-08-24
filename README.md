# rasuvaeff/understudy-phpunit

PHPUnit adapter for [rasuvaeff/understudy](https://github.com/rasuvaeff/understudy) —
a test double library where a configured call is a real call:
`when(fn () => $repo->find(123))->returns($book)`.

The trait ends every test with understudy's own bookkeeping done for you:

- **verify after success** — after a body that reaches
  `assertPostConditions()`, every `expect()` is checked. An expectation the
  code never fulfilled fails the test as an assertion failure;
- **original failure wins** — after a failing body nothing is verified, so
  the adapter can never mask the error that actually happened;
- **reset always** — an `#[After]` hook drops the context unconditionally.
  One test can never leak a double into the next;
- **early guard** — an `#[Before]` hook refuses to start over a context some
  earlier test left behind, which is what broken integration looks like.

> Using an AI coding assistant? [llms.txt](llms.txt) is a compact API
> reference it can load instead of guessing.

## Requirements

- PHP 8.3 – 8.5
- `phpunit/phpunit` (`^11.5 || ^12.0 || ^13.0`)
- `rasuvaeff/understudy` (`^0.1`)

Pest v2/v3 works too: it runs on PHPUnit, so the same trait applies through
`uses()`.

## Installation

```bash
composer require --dev rasuvaeff/understudy-phpunit
```

## Usage

```php
<?php

use function Rasuvaeff\Understudy\expect;
use function Rasuvaeff\Understudy\when;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

final class CheckoutTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testChargesForTheCart(): void
    {
        $books = Understudy::for(BookRepositoryInterface::class);
        when(fn () => $books->find(7))->returns($expected = new Book(7));

        $receipt = (new Checkout($books))->charge([7]);

        self::assertSame($expected->price, $receipt->total);
        expect(fn () => $books->find(7));   // exactly once — verified for you
    }
}
```

If the service never calls `find(7)`, the test fails after its body — with an
unmet-expectation report naming the call, not with a silent green.

### Strict stubs

A base class can flip strictness for a whole project:

```php
abstract class ProjectTestCase extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}
```

A stub configured but never called then fails its test — the Mockito reading
of "why did you configure it, then?". Per-double strictness stays available
through `Understudy::strict($double)` regardless of this setting.

### Overriding `assertPostConditions()` yourself

PHP resolves a method-name conflict between class and trait silently in
favour of the class — the trait's verification would stop running without any
error. Compose explicitly:

```php
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration {
    UnderstudyPHPUnitIntegration::assertPostConditions
        as understudyAssertPostConditions;
}

protected function assertPostConditions(): void
{
    $this->understudyAssertPostConditions();
    // your post-conditions ...
}
```

### Pest

Pest already owns the global `expect()` function, so import understudy's
setup verb under another name:

```php
use function Rasuvaeff\Understudy\expect as expectCall;

uses(UnderstudyPHPUnitIntegration::class);

it('charges for the cart', function () {
    $books = Understudy::for(BookRepositoryInterface::class);
    when(fn () => $books->find(7))->returns(new Book(7));

    (new Checkout($books))->charge([7]);

    expectCall(fn () => $books->find(7));
});
```

The collision-free static form `Understudy::when()/expect()/verify()` works
everywhere as well.

## API

| Member | Purpose |
|---|---|
| `UnderstudyPHPUnitIntegration` | The trait: verify-after-success, reset-in-`finally` semantics via `#[After]`, `#[Before]` guard, optional project-wide strict stubs |

Everything else — `for()`, `when()`, `expect()`, `verify()`, matchers,
forwarding, `wire()` — belongs to
[rasuvaeff/understudy](https://github.com/rasuvaeff/understudy) and is
documented there. This package adds no operations of its own.

## Examples

See [`examples/`](examples/README.md).

## Development

No PHP/Composer on the host — everything runs through Docker:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer test:integration
```

Or with Make: `make build`, `make cs-fix`, `make psalm`, `make test`.

The integration suite spawns real PHPUnit processes over fixture projects in
`tests/Integration/Fixtures/`; it needs no external services.

## License

[BSD-3-Clause](LICENSE.md)
