# Version detection

For every package found in a `path` repository, the plugin determines the
version using the following precedence (highest first). The **first** source that
yields a (valid) version wins.

| # | Source                                            | Validated? | Notes                                                              |
|---|---------------------------------------------------|------------|-------------------------------------------------------------------|
| 1 | `version` in the package `composer.json`          | no¹        | The package's own declared version – the canonical source.        |
| 2 | `extra."typo3/cms".version` in the `composer.json` | yes        | Convenient for TYPO3 extensions that carry their version in `extra`. |
| 3 | A sibling `VERSION` file next to `composer.json`  | yes        | First non-empty line, must be a parsable version.                 |
| 4 | *Stock Composer determination*                    | –          | `options.versions` → `COMPOSER_ROOT_VERSION` carry-over → git guess → `dev-main`. |

¹ Source 1 is returned verbatim, exactly as stock Composer would use it; it is
validated by Composer's package loader like any other declared version.

If none of sources 1–3 applies, the plugin defers to the **unmodified** stock
Composer logic (source 4), so existing behaviour is fully preserved.

## Source 1 – `composer.json` `version`

```json
{
    "name": "acme/my-extension",
    "version": "1.4.0"
}
```

→ resolved version: `1.4.0`.

## Source 2 – `extra."typo3/cms".version`

```json
{
    "name": "acme/my-extension",
    "extra": {
        "typo3/cms": {
            "version": "12.4.7"
        }
    }
}
```

→ resolved version: `12.4.7` (used only when no top-level `version` is set).

An unparsable value here is ignored and the next source is evaluated.

## Source 3 – sibling `VERSION` file

Place a `VERSION` file next to the package `composer.json`:

```
my-extension/
├── composer.json
└── VERSION
```

```
4.5.6
```

→ resolved version: `4.5.6`.

Rules for the `VERSION` file:

* The **first non-empty line** is used; trailing/leading whitespace is trimmed.
* The value must be parsable by Composer's
  [semantic version parser](https://github.com/composer/semver); otherwise the
  file is ignored and detection falls through to source 4.
* `v`-prefixed versions, four-segment versions and stability suffixes
  (e.g. `v12.4.0`, `12.4.0.0`, `1.0.0-beta1`) are accepted.

## Validation

Sources 2 and 3 are validated with `Composer\Semver\VersionParser::normalize()`.
A value that cannot be normalised is treated as "not present" and the next source
in the precedence list is evaluated. This prevents an unusable version string
from breaking the run while still honouring the documented precedence.

## Interaction with the `versions` option

The stock `path` repository lets you hard-code versions per package:

```json
{
    "type": "path",
    "url": "packages/*",
    "options": {
        "versions": {
            "acme/my-extension": "9.9.9"
        }
    }
}
```

The extended detection (sources 1–3) has a **higher precedence** than this
`versions` option. The option still applies when none of the extended sources
yields a version, preserving stock behaviour for packages without a detectable
version.
