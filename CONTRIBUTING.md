# Contributing

Thanks for considering a contribution! This project is held to the same quality
bar as TYPO3 Core work: correctness first, small and reviewable changes, and
standards-compliant commits.

## Getting started

1. Fork and clone the repository.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create a topic branch off `main`.

See [docs/development.md](docs/development.md) for the project layout and the
testing strategy.

## Before you open a pull request

Run the full QA suite locally and make sure it is green:

```bash
composer ci
```

This runs, in order:

* `composer ci:php:cs` – coding style (PHP CS Fixer). Auto-fix with
  `composer fix:cs`.
* `composer ci:php:stan` – static analysis (PHPStan, level `max`).
* `composer ci:tests:unit` – PHPUnit tests.

New behaviour must be covered by tests. The CI pipeline runs the same checks
across the supported PHP and Composer matrix.

## Coding standards

* Target **PHP 8.2+**; do not use APIs newer than the minimum supported PHP /
  Composer (`composer-plugin-api: ^2.9`).
* `declare(strict_types=1);` in every PHP file.
* Services are **stateless**; prefer constructor injection.
* Keep changes to Composer internals minimal and defensive (see the
  [architecture notes](docs/architecture.md)).

## Commit messages

Commit messages follow the
[TYPO3 Core commit message rules](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html):

* A subject line prefixed with a tag and **no longer than 52 characters**, e.g.
  `[FEATURE] …`, `[BUGFIX] …`, `[TASK] …`, `[DOCS] …`.
* A blank line, then a body wrapped at **72 characters** explaining *what* and
  *why*.
* Footer keywords where applicable.

As this project currently has no issue tracker, the `Resolves:` / `Releases:`
footer lines are omitted.

> [!IMPORTANT]
> Do **not** add a `Co-authored-by` trailer for any AI assistant.

### Example

```
[FEATURE] Detect version from sibling VERSION file

Add a third version detection source so that path packages shipping a
plain VERSION file next to their composer.json get a correct version
without an explicit "version" key.

The value is validated with composer's semantic version parser and only
used when no higher-precedence source applies.
```

## Reporting issues

When reporting a bug, please include your PHP and Composer versions, the relevant
`repositories` section of your `composer.json`, and the exact command and output.
