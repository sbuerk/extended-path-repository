# Installation

## Requirements

* PHP **8.2** or later
* Composer **2.9** or later (the plugin requires `composer-plugin-api: ^2.9`)

## Install the plugin

```bash
composer require sbuerk/extended-path-repository
```

Composer plugins must be allowed to run. When prompted, allow the plugin, or add
it to your root `composer.json`:

```json
{
    "config": {
        "allow-plugins": {
            "sbuerk/extended-path-repository": true
        }
    }
}
```

## Ordering matters

Composer creates the repositories declared in your root `composer.json` **before**
it activates any plugin. As a consequence the plugin can only influence a
Composer run in which it is **already installed**.

Two practical rules follow from this:

1. **Install the plugin before you rely on its behaviour.** If you are starting
   from scratch, require the plugin first and only afterwards add the
   `path` repositories that depend on the [tolerant matching](limitations.md)
   or [extended version detection](version-detection.md).

2. **Commit your `composer.lock`.** Day-to-day commands such as
   `composer install` (and any `composer update` after the first one) run with
   the plugin present in `vendor/`, so the extended behaviour applies from the
   very start of the run.

See [Limitations](limitations.md) for the precise bootstrap behaviour and the
one edge case where a stock error can still occur.

## Verifying the installation

After installation, a `path` repository whose `url` matches nothing no longer
breaks the run:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/*" }
    ]
}
```

With no `packages/` directory present, stock Composer aborts with
`The 'url' supplied for the path (packages/*) repository does not exist`, whereas
with the plugin installed the repository is simply skipped.
