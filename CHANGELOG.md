# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
