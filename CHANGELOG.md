# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.2.1 — 2026-09-05

- Allows `rasuvaeff/understudy` `^0.8 || ^1.0`. The one union this package
  will carry: it bridges the engine's 1.0 release, so that a project on the
  engine's 1.0 can keep this adapter without a window in which `composer
  require` silently installs the 0.8 engine beside it, and it is narrowed to
  `^1.0` in the release that follows the engine's.
- The two hook methods of the trait, `understudyPrepareContext()` and
  `understudyResetContext()`, are `@internal`. They are `protected` because
  PHPUnit discovers hooks by attribute and a trait cannot hide them from the
  class using it, not as override points: a subclass that overrode one would
  silently lose the guard or the reset. `understudyStrictStubs()` stays the
  one documented override, and `assertPostConditions()` stays public API
  because the README's explicit-composition recipe calls it through an alias.
- Both READMEs stop dating `lean()` to «understudy 0.4+»: with a floor of
  `^0.8` every engine this adapter installs beside has it. `llms.txt` carries
  the family's `# rasuvaeff/…` heading.

## 0.2.0 — 2026-09-05

- Requires `rasuvaeff/understudy` `^0.8`, and requires it as a single term. The
  accumulating union it carried (`^0.4 || ^0.5 || …`) had to be widened by hand
  on every core release, and a package that misses one becomes uninstallable
  beside its own engine.
- **`#[DoesNotPerformAssertions]` works again.** The trait counted an assertion
  unconditionally, so a test marked with that attribute reported "performed 1
  assertion" and went risky — on exactly the tests the attribute is written for
  ("this should simply not throw"), in a class that inherits the trait from a
  project-wide base class along with everything else. A test that created no
  double is now left alone entirely.
- **The context guard no longer blames a test that never ran.** A double
  created in `setUpBeforeClass()` fills the context before `#[Before]`, and the
  message sent the reader looking for an earlier test that skipped cleanup, an
  unused trait and a swallowed `assertPostConditions()` — none of which was the
  case. It now names `setUpBeforeClass()` first.
- **The Pest example runs.** The second snippet of that section called `when()`
  without importing it — `Call to undefined function when()` — in a section
  whose whole subject is which functions to import under which names. Both
  READMEs are fixed; the package's own Pest fixture had it right all along.
- Both READMEs and `llms.txt` stop pointing at `Understudy::strict($double)` as
  the per-double form of strict stubs. It is strict *dispatch* — "fail on any
  call no expectation matched" — and says nothing about a stub that was
  configured and never called; the per-double equivalent is
  `when(…)->times(n)`.
- Both READMEs and `llms.txt` say that verification runs **before** your
  teardown here and **after** it under `understudy-testo`, so a test whose
  expectation is fulfilled by teardown itself fails in one runner and passes in
  the other.

## 0.1.7 — 2026-09-04

- **Documentation review fixes.** Both READMEs gained the missing Security
  section; the API table no longer says «reset-in-`finally`» (the reset has
  always lived in `#[After]`). The trait's docblock now shows the explicit
  composition in the order the README mandates — the user's post-conditions
  first, verification second. The stale engine-constraint comment in
  `examples/readme-usage.php` caught up with `^0.7`. AGENTS.md no longer links
  to the retired `_plans/UNDERSTUDY-PLAN.md`.

## 0.1.6 — 2026-09-04

- Allow `rasuvaeff/understudy` `^0.7`. Widened rather than raised.
- The `#[Before]` guard is checked through real PHPUnit. It was covered only
  by a unit test calling `runGuard()` directly, which proves the condition and
  nothing about what a user sees — the guard fires from `#[Before]`, so the
  failure lands on the BLAMELESS class, the one that ran after the leak, and
  that attribution is the whole diagnostic. A fixture now leaks a context from
  a class without the trait and pins the report, the attribution and the exit
  code.
- The Pest recipe runs in CI, and it was broken. The test that executes it
  skips itself unless the fixture project is installed, which was every run of
  every job — so the recipe had never actually been checked, and the fixture
  still stubbed `find(7)` with `when()` and claimed the same call with
  `expect()`: two registrations of one call, which the engine has refused with
  `ConflictingExpectation` since 0.3. The README's Pest section has been right
  all along; the fixture that was supposed to prove it had drifted, in the
  third place this same mistake has now been found. The job has a checkout of
  its own because the fixture's path repositories are written for the monorepo
  layout — this package and the engine side by side — which is deliberate:
  the recipe runs against the working tree of both.
- `infection/infection` moves to `^0.35`, matching the monorepo templates.
  Re-measured: 12 of 12 mutants killed, unchanged.
- `AGENTS.md` said the unit tests avoid PHPUnit's assertion counter; they use
  it, by its new name. What actually guards the rename is the `PHPUnit ^11.5`
  matrix job, and the note says so now.

## 0.1.5 — 2026-09-04

- Allow `rasuvaeff/understudy` `^0.6`. Widened rather than raised: the adapter
  works against every 0.x line it has ever supported, and consumers on the
  older ones should not be cut off from it.
- **The shipped trait's docblock taught the usage this package documents as
  wrong.** It stubbed `find(7)` with `when()` and claimed the same call with
  `expect()` — two registrations of one call, which the engine refuses — and
  it armed the expectation below the action, where it counts zero. `llms.txt`
  carried the same snippet. Both now show the corrected form the README and
  `examples/readme-usage.php` have used since 0.1.0, and both name the two
  engine rules that decide it. Fixes #13.
- `DocumentedUsageTest` pins the three copies of the Usage snippet to each
  other. The original mistake survived two releases because nothing ran the
  snippet; the fix reached the README and the runnable example and stopped
  there, at the two copies nothing executes — which a consumer reads in their
  IDE and an assistant reads as `llms.txt`.

## 0.1.4 — 2026-09-03

- The Requirements section of both READMEs said `rasuvaeff/understudy`
  `^0.1 || ^0.2 || ^0.3` while `composer.json` has allowed `^0.4` since 0.1.3,
  and the usage examples already use 0.4 idioms.
- Allow `rasuvaeff/understudy` `^0.5`. The core release narrows what a
  closing `scope()` verifies and refuses two impossible matcher
  configurations — both changes to the consumer's own test code, neither
  reaching this adapter, which needs no code change.

## 0.1.3 — 2026-08-28

- Allow `rasuvaeff/understudy` `^0.4`: `Arg::rest()`, `Arg::captor()`,
  `Understudy::delegate()`, `Understudy::lean()` and rendered property hooks
  are all additive — the adapter needs no code change.

- README (EN+RU): documented that the `#[After]` reset runs *after*
  `tearDown()` while the call log still retains returned values, the Windows
  "Directory not empty" failure that surfaces it, and the two remedies —
  `Understudy::lean()` (understudy 0.4+) and `Understudy::scope()`
  (rasuvaeff/understudy#63).

## 0.1.2 — 2026-08-27

- Allow `rasuvaeff/understudy` `^0.3` (the engine refuses colliding same-call
  `when()`/`expect()` registrations with `ConflictingExpectation` from 0.3.0;
  nothing in this adapter changes behaviour).

## 0.1.1 — 2026-08-27

- Accept `rasuvaeff/understudy` 0.2 alongside 0.1. Nothing in the adapter
  changes; the core's 0.2.0 is additive, and on 0.x Composer's caret treats a
  minor as a boundary, so the constraint has to say so explicitly. Widening it
  breaks no existing install.

- **The release workflow waits for the matrix build instead of judging it
  mid-flight.** A tag pushed right after the merge arrived while master's own
  build was still running, and the guard read a `null` conclusion as a failed
  one, refusing to create the GitHub Release. Hit for real on the core package
  while tagging `v0.1.1`. No effect on the package itself.

## 0.1.0 — 2026-08-25

- Initial development: the `UnderstudyPHPUnitIntegration` trait —
  verify-after-success via `assertPostConditions()`, unconditional reset in an
  `#[After]` hook, `#[Before]` guard over leaked contexts, optional
  project-wide strict stubs through `understudyStrictStubs()`. PHPUnit
  11.5/12/13; Pest works through `uses()`.
- Changed: `parent::assertPostConditions()` now runs before verification, so a
  base class's own post-conditions are no longer skipped when an expectation
  is unmet.
- `composer build` now runs the integration suite as well, and CI pins each
  supported PHPUnit major in its own matrix job.
- Added `@psalm-require-extends TestCase` to the trait.
- An integration fixture pins that a test whose only check is an understudy
  expectation is not reported risky by PHPUnit's strictness about tests that
  assert nothing.
