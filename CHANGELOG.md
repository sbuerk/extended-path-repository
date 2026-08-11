# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-08-11

### Changed

- Lowered the minimum PHP requirement from 8.2 to **8.1**, so the plugin can be
  used in projects that still support TYPO3 v12 and therefore still build on
  PHP 8.1. No source change was needed: the codebase uses no PHP 8.2 syntax or
  functions. PHP 8.1 remains the floor because
  `Repository\RepositoryReplacer` relies on `ReflectionProperty` granting
  access to private members without `setAccessible()`, which is 8.1 behaviour.
- Widened the `phpunit/phpunit` development requirement to `^10.5 || ^11.0`.
  PHPUnit 11 requires PHP 8.2 and would otherwise have blocked installation on
  PHP 8.1; the test suite uses nothing that is exclusive to PHPUnit 11.
- The continuous integration test matrix gained PHP 8.1, and the quality
  assurance job now runs on 8.1 — the minimum supported version, as
  `docs/development.md` asks for.

## [1.0.1] - 2026-07-05

### Added

- Composer plugin-ordering hints in the `composer.json` `extra` section
  (`plugin-modifies-downloads`, `plugin-modifies-install-path`,
  `plugin-optional: false`) so the plugin is activated as early as possible –
  before normal packages are downloaded and installed.

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

[1.1.0]: https://github.com/sbuerk/extended-path-repository/releases/tag/1.1.0
[1.0.1]: https://github.com/sbuerk/extended-path-repository/releases/tag/1.0.1
[1.0.0]: https://github.com/sbuerk/extended-path-repository/releases/tag/1.0.0
