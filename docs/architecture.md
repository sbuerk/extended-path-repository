# How it works (architecture)

This document explains how the plugin integrates with Composer, why it is built
the way it is, and how the responsibilities are split across the classes. It is
aimed at contributors and at developers who want to extend the plugin.

## The Composer bootstrap ordering problem

Two facts about Composer's bootstrap drive the entire design:

1. **The root `composer.json` is validated against Composer's bundled JSON
   schema.** That schema only permits the well-known repository `type` values
   (`composer`, `vcs`, `path`, `artifact`, …). A brand-new custom type such as
   `extended-path` is rejected *before any plugin code runs*. A custom type name
   is therefore not a viable option.

2. **Root repositories are instantiated before plugins are activated.** During
   `Composer\Factory::createComposer()` the root package is loaded
   (`RootPackageLoader` → `RepositoryFactory::defaultRepos()` →
   `RepositoryManager::createRepository()`) and the repository objects are
   created. Only *afterwards* are installed plugins loaded and
   `PluginInterface::activate()` is called.

Consequently:

* The plugin must reuse the existing `path` type (schema-valid, identical
  configuration syntax).
* Registering the extended class via
  `RepositoryManager::setRepositoryClass('path', …)` in `activate()` only affects
  repositories created *after* activation. The repositories declared in the root
  `composer.json` already exist as stock `PathRepository` instances by then.

## The approach

On activation the plugin does two things:

1. **Register the extended class for future repositories.**
   `setRepositoryClass('path', ExtendedPathRepository::class)` ensures any
   `path` repository created from this point on uses the extended implementation.

2. **Upgrade the already-created repositories in place.** The
   [`RepositoryReplacer`](../src/Repository/RepositoryReplacer.php) walks the
   repositories already held by the `RepositoryManager` and swaps every *stock*
   `PathRepository` for a freshly created `ExtendedPathRepository` (rebuilt from
   the same repository config via `RepositoryManager::createRepository()`, so
   `only`/`exclude`/`canonical` filtering is preserved).

The heavy lifting of a path repository (`initialize()`, which reads the
`composer.json` files and detects versions) is *lazy* – it runs during dependency
resolution, well after activation. So once the objects are swapped, the extended
logic is what actually executes.

### Why reflection?

`RepositoryManager` exposes no public API to replace or remove a repository, and
`FilterRepository` exposes no setter for its wrapped repository. The replacer
therefore uses a minimal, defensive amount of reflection on two private
properties:

* `Composer\Repository\RepositoryManager::$repositories`
* `Composer\Repository\FilterRepository::$repo`

If a future Composer release renames or removes these properties, the replacer
**degrades gracefully**: it emits a verbose warning and leaves the stock path
repositories untouched, so nothing breaks – the extended behaviour simply does
not apply to the pre-created repositories.

## Class responsibilities

| Class | Responsibility |
|-------|----------------|
| [`Plugin`](../src/Plugin.php) | Composer plugin entry point. Registers the extended class and triggers the replacement on `activate()`. |
| [`Repository\ExtendedPathRepository`](../src/Repository/ExtendedPathRepository.php) | A subclass of `Composer\Repository\PathRepository` that reimplements `initialize()` to add tolerant matching and extended version detection. |
| [`Repository\RepositoryReplacer`](../src/Repository/RepositoryReplacer.php) | Swaps already-instantiated stock path repositories (including `FilterRepository`-wrapped ones) for the extended implementation. |
| [`Version\VersionResolver`](../src/Version/VersionResolver.php) | Stateless service implementing the version precedence (see [version detection](version-detection.md)). |

## About the reimplemented `initialize()`

`PathRepository::initialize()` keeps all of its relevant state `private` and
offers no extension seam, so `ExtendedPathRepository` reimplements the method.
The body is kept **faithful to the Composer 2.9 baseline** (the minimum supported
version) with exactly two deviations:

* an empty match set returns early instead of throwing (tolerant matching);
* the version is resolved through `VersionResolver` before the stock fallback
  chain (extended version detection).

The git revision lookup uses the portable `git log -n1 --pretty=%H` form, which
works across all supported Composer versions (Composer 2.10 introduced internal
helpers that do not exist in 2.9; avoiding them keeps a single code path that is
valid everywhere).

> [!NOTE]
> Because `initialize()` is a faithful copy of upstream, it is the most likely
> place to need attention when Composer changes its `PathRepository`. See the
> [development guide](development.md) for how the test suite guards the
> behaviour.
