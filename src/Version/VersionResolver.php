<?php

declare(strict_types=1);

/*
 * This file is part of the "sbuerk/extended-path-repository" composer plugin.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the GNU General Public License, either version 2 of the License, or any
 * later version.
 *
 * For the full copyright and license information, please read the LICENSE file
 * that was distributed with this source code.
 */

namespace SBUERK\ExtendedPathRepository\Version;

use Composer\Semver\VersionParser;

/**
 * Stateless service implementing the extended package version detection used by
 * {@see \SBUERK\ExtendedPathRepository\Repository\ExtendedPathRepository}.
 *
 * The version is resolved using the following precedence (highest first):
 *
 *  1. an explicit `version` defined in the package `composer.json`,
 *  2. an `extra."typo3/cms".version` value defined in the package `composer.json`,
 *  3. a sibling `VERSION` file (next to `composer.json`) holding a parsable version.
 *
 * When none of these sources yields a (valid) version, {@see self::resolve()}
 * returns `null` and the caller falls back to the stock composer determination
 * (path `options.versions`, `COMPOSER_ROOT_VERSION` carry over, git guessing).
 *
 * Sources 2 and 3 are validated against composer's semantic version parser; an
 * unparsable value is ignored and the next source in the precedence list is
 * evaluated. Source 1 is intentionally returned verbatim because it is the
 * package's own declared version and is validated by composer's package loader
 * exactly like it would be without this plugin.
 */
final class VersionResolver
{
    private const VERSION_FILE = 'VERSION';

    private VersionParser $versionParser;

    public function __construct(?VersionParser $versionParser = null)
    {
        $this->versionParser = $versionParser ?? new VersionParser();
    }

    /**
     * Resolve the version for a single path package.
     *
     * @param array<string, mixed> $packageConfig the decoded package `composer.json`
     * @param string               $packagePath   absolute path of the directory containing `composer.json`
     *
     * @return string|null the detected version, or null when no extended source applies
     */
    public function resolve(array $packageConfig, string $packagePath): ?string
    {
        // 1. Explicit "version" from composer.json (source of truth, returned verbatim).
        $version = $packageConfig['version'] ?? null;
        if (is_string($version) && trim($version) !== '') {
            return trim($version);
        }

        // 2. extra."typo3/cms".version
        $extraVersion = $this->extractExtraTypo3CmsVersion($packageConfig);
        if ($extraVersion !== null && $this->isParsableVersion($extraVersion)) {
            return $extraVersion;
        }

        // 3. Sibling VERSION file.
        return $this->readVersionFile($packagePath);
    }

    /**
     * Extract a non-empty `extra."typo3/cms".version` string, if present.
     *
     * @param array<string, mixed> $packageConfig
     */
    private function extractExtraTypo3CmsVersion(array $packageConfig): ?string
    {
        $extra = $packageConfig['extra'] ?? null;
        if (!is_array($extra) || !isset($extra['typo3/cms']) || !is_array($extra['typo3/cms'])) {
            return null;
        }

        $version = $extra['typo3/cms']['version'] ?? null;
        if (!is_string($version)) {
            return null;
        }

        $version = trim($version);

        return $version === '' ? null : $version;
    }

    /**
     * Read and validate the first non-empty line of a sibling `VERSION` file.
     */
    private function readVersionFile(string $packagePath): ?string
    {
        $versionFile = rtrim($packagePath, '/\\') . DIRECTORY_SEPARATOR . self::VERSION_FILE;
        if (!is_file($versionFile) || !is_readable($versionFile)) {
            return null;
        }

        $contents = @file_get_contents($versionFile);
        if ($contents === false) {
            return null;
        }

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $candidate = trim($line);
            if ($candidate === '') {
                continue;
            }

            return $this->isParsableVersion($candidate) ? $candidate : null;
        }

        return null;
    }

    private function isParsableVersion(string $version): bool
    {
        try {
            $this->versionParser->normalize($version);

            return true;
        } catch (\UnexpectedValueException) {
            return false;
        }
    }
}
