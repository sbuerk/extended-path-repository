# Documentation

`sbuerk/extended-path-repository` is a Composer plugin that supersedes the
built-in [`path` repository](https://getcomposer.org/doc/05-repositories.md#path)
with two additional behaviours while keeping the stock configuration syntax:

1. **Extended version detection** for path packages.
2. **Tolerant path matching** – non-matching `url`s are ignored instead of
   raising an error.

## For everyone

If you just want to use the plugin:

* [Installation](installation.md) – how to add the plugin and why ordering
  matters.
* [Configuration & usage](configuration.md) – what changes for your `path`
  repositories (spoiler: the configuration syntax does not change).
* [Version detection](version-detection.md) – the exact precedence used to
  determine a package's version, including the `VERSION` file format.

## For contributors and integrators

If you want to understand, extend, or contribute to the plugin:

* [How it works (architecture)](architecture.md) – the Composer bootstrap
  ordering problem, how the plugin overrides the `path` type, and how the class
  responsibilities are split.
* [Limitations](limitations.md) – the trade-offs and edge cases you should be
  aware of.
* [Development & testing](development.md) – local toolchain, running the QA
  suite, and the project layout.
* [Contributing](../CONTRIBUTING.md) – workflow, coding standards and commit
  message rules.

## At a glance

| Topic                       | Stock `path` repository                     | `extended-path-repository`                                       |
|-----------------------------|---------------------------------------------|------------------------------------------------------------------|
| Configuration `type`        | `path`                                      | `path` (unchanged)                                               |
| `url` matches nothing       | throws `… repository does not exist`        | silently ignored                                                 |
| Version source (precedence) | `options.versions` → root version → guesser | `composer.json` `version` → `extra."typo3/cms".version` → `VERSION` file → *(stock determination)* |
