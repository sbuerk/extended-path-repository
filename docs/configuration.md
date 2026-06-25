# Configuration & usage

> [!NOTE]
> The plugin **does not introduce a new repository type** and **does not add any
> new configuration keys**. It reuses the stock `path` repository type and its
> configuration syntax. Everything documented for the
> [official `path` repository](https://getcomposer.org/doc/05-repositories.md#path)
> keeps working unchanged.

Once the plugin is installed, every `path` repository in your project is upgraded
automatically. There is nothing to opt into.

## Example

```json
{
    "repositories": [
        { "type": "path", "url": "packages/*" },
        { "type": "path", "url": "../shared/my-library" },
        {
            "type": "path",
            "url": "vendor-local/*",
            "options": {
                "symlink": false
            }
        }
    ],
    "require": {
        "acme/my-library": "*",
        "acme/some-package": "*"
    }
}
```

## What changes compared to the stock `path` repository

### 1. Non-matching `url`s are ignored

The stock `path` repository aborts the whole Composer run when the configured
`url` matches nothing:

```
The `url` supplied for the path (packages/*) repository does not exist
```

With the plugin, such a repository is **silently skipped**. This is convenient
when a glob legitimately matches nothing in some setups (e.g. an optional
`packages/*` directory that only exists in development checkouts).

> [!WARNING]
> A consequence of this tolerance is that a **mistyped path** is silently ignored
> as well. If a required package is "not found", double-check the `url` of your
> `path` repositories. See [Limitations](limitations.md).

### 2. Extended version detection

When a path package does not declare an explicit `version`, the plugin tries
additional sources before falling back to Composer's stock determination. The
full precedence and the `VERSION` file format are documented in
[Version detection](version-detection.md).

## Options

All stock `path` repository `options` continue to work, including `symlink`,
`reference`, `relative` and `versions`. Note that the extended version detection
takes precedence over the `versions` option – see
[Version detection](version-detection.md#interaction-with-the-versions-option).
