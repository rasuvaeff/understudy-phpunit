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
