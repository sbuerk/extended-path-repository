# sbuerk/extended-path-repository

[![Latest release](https://img.shields.io/github/v/release/sbuerk/extended-path-repository?label=release&sort=semver)](https://github.com/sbuerk/extended-path-repository/releases/latest)

A [Composer](https://getcomposer.org/) plugin that extends the built-in
[`path` repository](https://getcomposer.org/doc/05-repositories.md#path) with:

* **Extended version detection** – derive a path package's version from its
  `composer.json` `version`, from `extra."typo3/cms".version`, or from a sibling
  `VERSION` file, before falling back to Composer's stock determination.
* **Tolerant path matching** – a configured `url` that matches nothing (a missing
  directory or an empty glob) is silently ignored instead of aborting the whole
  Composer run.

The plugin keeps the **exact same configuration syntax** as the stock `path`
repository (`"type": "path"`); once installed it transparently upgrades every
`path` repository in your project.

```json
{
    "repositories": [
        { "type": "path", "url": "packages/*" }
    ]
}
```

## Installation

```bash
composer require sbuerk/extended-path-repository
```

> [!IMPORTANT]
> Because Composer instantiates repositories before plugins are activated, the
> plugin must already be installed for its behaviour to apply. Install it first,
> then add your (extended / tolerant) `path` repositories – see
> [docs/installation.md](docs/installation.md) and
> [docs/limitations.md](docs/limitations.md).

## Requirements

* PHP **8.2+**
* Composer **2.9+** (`composer-plugin-api: ^2.9`)

## Documentation

Short overview here – details live in [`docs/`](docs/README.md):

* [Installation](docs/installation.md)
* [Configuration & usage](docs/configuration.md)
* [Version detection](docs/version-detection.md)
* [How it works (architecture)](docs/architecture.md)
* [Limitations](docs/limitations.md)
* [Development & testing](docs/development.md)

## Contributing

Contributions are welcome – please read [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Released under the [GNU General Public License v2.0 or later](LICENSE).
