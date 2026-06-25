# Development & testing

## Requirements

* PHP **8.2+**
* Composer **2.9+**

## Setup

```bash
composer install
```

## Quality assurance

The project ships composer script shortcuts that mirror what the CI pipeline
runs:

| Command | Description |
|---------|-------------|
| `composer ci:php:cs`    | Coding style check (PHP CS Fixer, dry-run).            |
| `composer fix:cs`       | Apply coding style fixes.                              |
| `composer ci:php:stan`  | Static analysis (PHPStan, level `max`).               |
| `composer ci:tests:unit`| Unit/functional tests (PHPUnit).                      |
| `composer ci`           | Run style check, static analysis and tests in order.  |

Run the full suite:

```bash
composer ci
```

> [!TIP]
> Run PHP CS Fixer with PHP 8.2 to avoid the "running on a newer PHP version"
> notice and to match the project's minimum supported version.

## Project layout

```
.
├── src/
│   ├── Plugin.php                         # Composer plugin entry point
│   ├── Repository/
│   │   ├── ExtendedPathRepository.php     # extended path repository
│   │   └── RepositoryReplacer.php         # in-place upgrade of existing repos
│   └── Version/
│       └── VersionResolver.php            # stateless version precedence logic
├── tests/
│   ├── Fixtures/                          # fixture packages (composer.json + VERSION)
│   └── Unit/                              # PHPUnit tests
├── docs/                                  # this documentation
├── .github/workflows/ci.yml               # CI pipeline
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon.dist
└── .php-cs-fixer.dist.php
```

## Testing strategy

* **`VersionResolverTest`** – pure unit tests of the version precedence,
  including invalid-source fall-through and the `VERSION` file parsing rules.
* **`ExtendedPathRepositoryTest`** – functional tests that instantiate the
  repository against fixture packages and assert the resolved versions, the
  precedence ordering, and the tolerant matching (missing path / empty glob /
  directory without `composer.json`).
* **`RepositoryReplacerTest`** – verifies that stock `PathRepository` instances
  (bare and `FilterRepository`-wrapped) are swapped for the extended class, and
  that unrelated or already-extended repositories are left untouched.

The functional tests use the real Composer classes (pulled in via the
`composer/composer` dev dependency), so they double as a guard against upstream
`PathRepository` changes within the supported Composer range.

## A note on the local toolchain

This repository is developed with [`direnv`](https://direnv.net/); the
`.envrc` provides PHP 8.2 and Composer 2 on `PATH`. The `.envrc` file is
intentionally git-ignored. If you use the same setup, run `direnv allow` once in
the project directory.
