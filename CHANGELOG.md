# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-25

### Added

- Composer plugin overriding the built-in `path` repository type.
- Extended package version detection with the precedence: `composer.json`
  `version` → `extra."typo3/cms".version` → sibling `VERSION` file → stock
  Composer determination.
- Tolerant path matching: a `url` that matches nothing is silently ignored
  instead of aborting the Composer run.
- In-place upgrade of `path` repositories already created from the root
  `composer.json`, including `FilterRepository`-wrapped ones.
- Documentation under `docs/`, unit/functional test suite, and a GitHub Actions
  CI pipeline.

[Unreleased]: https://github.com/sbuerk/extended-path-repository/compare/1.0.0...HEAD
[1.0.0]: https://github.com/sbuerk/extended-path-repository/releases/tag/1.0.0
