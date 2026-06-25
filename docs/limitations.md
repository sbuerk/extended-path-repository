# Limitations

The plugin is intentionally small and reuses Composer's own `path` repository.
The following trade-offs and edge cases are worth knowing.

## 1. The plugin must already be installed

Composer instantiates the repositories of the root `composer.json` **before**
plugins are activated (see [architecture](architecture.md)). The extended
behaviour therefore only applies to a Composer run in which the plugin is already
present in `vendor/`.

**Edge case – the very first install.** If your root `composer.json` already
contains a `path` repository whose `url` matches nothing *and* the plugin is not
yet installed, the **first** `composer require sbuerk/extended-path-repository`
(or `composer update`) can still fail with the stock error
`The 'url' supplied for the path (…) repository does not exist`, because the stock
`PathRepository` runs before the plugin is activated.

**Workarounds:**

* Install the plugin first, *then* add the tolerant / extended `path`
  repositories.
* Commit your `composer.lock`. Every subsequent `composer install` runs with the
  plugin present, so the tolerant behaviour applies from the start.

## 2. The behaviour is global

Once installed, the plugin upgrades **every** `path` repository in the project.
There is no per-repository opt-in/opt-out. This matches the intended use case
(replacing the default `path` behaviour project-wide) but means that:

* a **mistyped `url`** is silently ignored everywhere, which can make a missing
  package harder to diagnose. When a required package is unexpectedly "not
  found", verify your `path` repository `url`s.

## 3. The extended version detection only adds higher-precedence sources

The plugin never *removes* a stock version source; it only adds sources 1–3 with
a higher precedence (see [version detection](version-detection.md)). If none of
them applies, behaviour is identical to stock Composer.

## 4. Reliance on Composer internals

To upgrade already-created repositories, the plugin reflects on two private
Composer properties (`RepositoryManager::$repositories` and
`FilterRepository::$repo`) and reimplements `PathRepository::initialize()`. These
are tied to Composer internals:

* If the private properties change, the in-place replacement **degrades
  gracefully** (a verbose warning is emitted; stock path repositories keep
  working).
* If `PathRepository::initialize()` changes upstream, the reimplemented method
  may need to be re-synced. The supported range is Composer `^2.9`; the
  implementation is verified against this range in CI.
