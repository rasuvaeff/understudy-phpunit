# AGENTS.md — understudy-phpunit

Guidance for AI agents working on this package. Read before changing code.

## What this is

The PHPUnit adapter of the understudy family — a thin layer over
`rasuvaeff/understudy` that ends every test with the library's bookkeeping
done for the user. It ships exactly one trait:

- `Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration` —
  verify-after-success via `assertPostConditions()`, unconditional reset in
  an `#[After]` hook, `#[Before]` guard over leaked contexts, optional
  project-wide strict stubs through `understudyStrictStubs()`.

Everything algorithmic — doubles, expectations, matchers, verification — lives
in the core package; do not fix engine behaviour from here. The design and its
milestones live in the monorepo at `_plans/UNDERSTUDY-PLAN.md` (§6.7 is the
adapter lifecycle contract).

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **The adapter adds no operations and no state.** Every doubling operation
   belongs to the core facade; the trait may only call `Understudy::verifyAll()`,
   `Understudy::reset()` and `Understudy::idle()`, read nothing internal, and
   keep no mutable fields. A change that grows either list is a design
   decision to be made in the plan first.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

The core package (`rasuvaeff/understudy`) has no release yet, so during
development it resolves through a temporary path repository. Run from the
monorepo root, with the whole root mounted so the sibling package is visible:

```bash
docker run --rm -v "$PWD":/repo -w /repo/understudy-phpunit composer:2 sh -c '
    git config --global --add safe.directory /repo/understudy-phpunit
    composer config repositories.core "{\"type\":\"path\",\"url\":\"../understudy\",\"options\":{\"versions\":{\"rasuvaeff/understudy\":\"0.1.0\"}}}"
    composer update
    composer config --unset repositories.core
    rm composer.lock
'
```

Never commit that `repositories` key or a `composer.lock`.

Otherwise, as usual:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer test:integration
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- **The lifecycle table (plan §6.7) is the contract.** Passed body (i.e.
  `assertPostConditions()` reached) → verify; anything else → pass through
  untouched; always reset. The reset lives in `#[After]`, not in post-
  conditions, precisely because PHPUnit skips post-conditions after a failing
  body — that ordering IS why original failures are never masked.
- **`parent::assertPostConditions()` runs BEFORE verification.** A post-
  condition the user wrote is closer to the test body than this bookkeeping,
  so it must run at all and its failure must be the one reported. Verifying
  first meant an unmet expectation threw ahead of the parent chain and the
  user's post-conditions were silently skipped.
- **`addToAssertionCount(1)` lands before PHPUnit's own risky verdict, and
  that is load-bearing.** A test whose only check is an understudy expectation
  asserts nothing itself, and PHPUnit is strict about that by default. The
  trait's count reaches the runner in time, so such a test is not risky —
  where the Testo adapter, being outer to the equivalent decision, has to take
  the verdict back after the fact instead. Pinned by the
  `NoAssertionsOfItsOwn` fixture, control test included: without the control
  proving the strict setting is armed, the other half passing means nothing.
- **The integration suite is part of `composer build`.** It is the only place
  the PHPUnit process-isolation cell and the `addToAssertionCount(1)` contract
  are exercised against a real runner; a suite no gate runs is a suite that
  rots. `phpunit-versions` in CI pins each supported PHPUnit major separately,
  because `composer install` only ever resolves one of the three.
- **A verification failure must surface as an assertion failure**, wrapped in
  `AssertionFailedError` with the core's `VerificationFailed` as previous.
  Letting it propagate raw would report a broken expectation as an error and
  mislead the reader about what went wrong.
- **Trait-vs-class shadowing is silent in PHP**: a class method named
  `assertPostConditions()` wins over the trait's without any error. The README
  documents explicit alias composition for it; never "fix" this with tricks
  like method_exists probing at runtime.
- **`#[Before]`/`#[After]` hooks work from traits** — that is how the guard and
  the reset get invoked without the user writing anything. But
  `#[\Override]` on a class method overriding a *trait*-provided method is a
  fatal ("no matching parent"): PHP resolves `#[\Override]` against parents
  and interfaces only. Fixture subclasses override `understudyStrictStubs()`
  without the attribute, on purpose.
- **PHPUnit renamed its assertion counter** (`getNumAssertions()` →
  `numberOfAssertionsPerformed()`) between supported major versions. Unit
  tests therefore assert observable state instead of the counter; the "+1
  assertion" contract is pinned by the integration fixtures, where real
  PHPUnit prints the counts.
- **Integration fixtures are real Composer-independent projects**: each has
  its own `phpunit.xml` pointing at this package's `vendor/autoload.php`, and
  fixture classes autoload PSR-4 through the dev mappings of THIS package.
  That means fixture directories must be PascalCase matching their namespaces
  (`UnmetExpectation`, not `unmet-expectation`), or the subprocess cannot find
  the classes.
- **Process isolation re-runs the bootstrap per child process** — which is
  exactly what makes `bypassFinals()` work there, and exactly why the claim
  needs a dedicated fixture (`ProcessIsolation/`). Keep `cacheResult="false"`
  in those `phpunit.xml` files: PHPUnit's result cache writes go through our
  own `file://` stream wrapper and warn about unsupported locks otherwise.
- **Exit codes of the spawned phpunit differ by outcome** (failures → 1,
  errors → 2, success → 0); pin them per scenario rather than asserting
  "non-zero".
- Every unit-test class resets the context in `#[BeforeTest]`. The trait resets
  after each run, but our own suite runs under Testo without the trait — an
  assertion failing mid-scenario would otherwise leak state into the next.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types, named arguments.
- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.
- **CI workflows are SHA-pinned.** Every `uses:` in `.github/workflows/*.yml`
  references a 40-char commit SHA with a `# vN` trailing comment. Never revert
  to floating `@vN` tags. Updates go through Dependabot. Workflows carry
  `permissions: { contents: read }` and `persist-credentials: false` on every
  checkout. Verify with `zizmor --persona=auditor .github/`.

## When you finish

- Update `README.md` **and `README.ru.md`** (both languages, same commit;
  and `examples/` if usage changed); update `CHANGELOG.md` when releasing.
- Re-run `composer build`; if the change affects public API or release safety,
  also run `make release-check`. Paste the output.
