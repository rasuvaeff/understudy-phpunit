# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- The `#[Before]` guard is checked through real PHPUnit. It was covered only
  by a unit test calling `runGuard()` directly, which proves the condition and
  nothing about what a user sees — the guard fires from `#[Before]`, so the
  failure lands on the BLAMELESS class, the one that ran after the leak, and
  that attribution is the whole diagnostic. A fixture now leaks a context from
  a class without the trait and pins the report, the attribution and the exit
  code.
- The Pest recipe runs in CI. The test that executes it skips itself unless
  the fixture project is installed, which was every run of every job, so the
  README's Pest recipe had never actually been checked. It has a job of its
  own because Pest pins one PHPUnit major and this package supports three.
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
