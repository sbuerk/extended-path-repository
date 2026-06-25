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

namespace SBUERK\ExtendedPathRepository\Repository;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionParser;
use Composer\Repository\PathRepository;
use Composer\Util\Filesystem;
use Composer\Util\Git as GitUtil;
use Composer\Util\HttpDownloader;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use SBUERK\ExtendedPathRepository\Version\VersionResolver;

/**
 * Drop-in replacement for composer's {@see PathRepository} that:
 *
 *  1. uses an extended package version detection (see {@see VersionResolver}),
 *  2. silently ignores a configured `url` that does not match anything instead
 *     of aborting the whole composer run with an exception.
 *
 * It deliberately keeps the stock configuration syntax (`"type": "path"`) and
 * the stock behaviour for everything not explicitly listed above. The extended
 * version detection only ever *adds* sources with a higher precedence than the
 * stock determination; when none of them applies the original composer logic
 * (path `options.versions`, `COMPOSER_ROOT_VERSION`, git guessing) runs
 * unchanged.
 *
 * Implementation note: {@see PathRepository::initialize()} keeps all relevant
 * state private and offers no extension seam, so the method is reimplemented
 * here. The body is kept faithful to the composer 2.9 baseline (the minimum
 * supported version); the git revision lookup uses the portable
 * `git log -n1 --pretty=%H` form that works across all supported composer
 * versions. The shadowed properties below mirror the parent state needed by the
 * reimplemented method.
 *
 * @phpstan-type RepoOptions array{symlink?: bool, reference?: string, relative?: bool, versions?: array<string, string>}
 * @phpstan-type RepoConfig array{url?: string, options?: RepoOptions}
 */
class ExtendedPathRepository extends PathRepository
{
    private ArrayLoader $extendedLoader;
    private VersionGuesser $extendedVersionGuesser;
    private VersionResolver $extendedVersionResolver;
    private string $extendedUrl;
    private ProcessExecutor $extendedProcess;

    /**
     * @var RepoOptions
     */
    private array $extendedOptions;

    /**
     * @param RepoConfig $repoConfig the stock path repository configuration
     */
    public function __construct(
        array $repoConfig,
        IOInterface $io,
        Config $config,
        ?HttpDownloader $httpDownloader = null,
        ?EventDispatcher $dispatcher = null,
        ?ProcessExecutor $process = null,
        ?VersionResolver $versionResolver = null
    ) {
        parent::__construct($repoConfig, $io, $config, $httpDownloader, $dispatcher, $process);

        // The parent constructor validated `url`, so it is present from here on.
        $this->extendedLoader = new ArrayLoader(null, true);
        $this->extendedUrl = Platform::expandPath($repoConfig['url'] ?? '');
        $this->extendedProcess = $process ?? new ProcessExecutor($io);
        $this->extendedVersionGuesser = new VersionGuesser($config, $this->extendedProcess, new VersionParser(), $io);
        $this->extendedOptions = $repoConfig['options'] ?? [];
        if (!isset($this->extendedOptions['relative'])) {
            $filesystem = new Filesystem();
            $this->extendedOptions['relative'] = !$filesystem->isAbsolutePath($this->extendedUrl);
        }
        $this->extendedVersionResolver = $versionResolver ?? new VersionResolver();
    }

    /**
     * Read the configured path(s) and register the found package(s).
     *
     * Reimplemented from {@see PathRepository::initialize()} with the two
     * behavioural changes described in the class docblock.
     */
    protected function initialize(): void
    {
        // Equivalent to ArrayRepository::initialize(); we cannot call the parent
        // PathRepository::initialize() because it would re-apply the stock logic
        // (and throw on an empty match).
        $this->packages = [];

        $urlMatches = $this->getExtendedUrlMatches();

        // Extended behaviour #2: never fail when the configured url does not
        // match anything (missing directory or empty glob). Silently ignore.
        if ($urlMatches === []) {
            return;
        }

        foreach ($urlMatches as $url) {
            $path = realpath($url) . DIRECTORY_SEPARATOR;
            $composerFilePath = $path . 'composer.json';

            if (!file_exists($composerFilePath)) {
                continue;
            }

            $json = (string) file_get_contents($composerFilePath);
            $decoded = JsonFile::parseJson($json, $composerFilePath);
            if (!is_array($decoded)) {
                continue;
            }
            /** @var array<string, mixed> $package */
            $package = $decoded;
            $package['dist'] = [
                'type' => 'path',
                'url' => $url,
            ];
            $reference = $this->extendedOptions['reference'] ?? 'auto';
            if ('none' === $reference) {
                $package['dist']['reference'] = null;
            } elseif ('config' === $reference || 'auto' === $reference) {
                $package['dist']['reference'] = hash('sha1', $json . serialize($this->extendedOptions));
            }

            // copy symlink/relative options to transport options
            $package['transport-options'] = array_intersect_key($this->extendedOptions, ['symlink' => true, 'relative' => true]);

            // Extended behaviour #1: determine the version using the extended
            // precedence (composer.json version > extra "typo3/cms".version >
            // sibling VERSION file). Only when none of these applies do we fall
            // back to the stock determination below.
            $resolvedVersion = $this->extendedVersionResolver->resolve($package, $path);
            if ($resolvedVersion !== null) {
                $package['version'] = $resolvedVersion;
            }

            // use the version provided as option if available (stock behaviour),
            // unless the extended detection already provided one.
            $packageName = isset($package['name']) && is_string($package['name']) ? $package['name'] : null;
            if (!isset($package['version']) && $packageName !== null && isset($this->extendedOptions['versions'][$packageName])) {
                $package['version'] = $this->extendedOptions['versions'][$packageName];
            }

            // carry over the root package version if this path repo is in the
            // same git repository as the root package (stock behaviour)
            if (!isset($package['version']) && ($rootVersion = Platform::getEnv('COMPOSER_ROOT_VERSION'))) {
                if (
                    0 === $this->extendedProcess->execute(['git', 'rev-parse', 'HEAD'], $ref1, $path)
                    && 0 === $this->extendedProcess->execute(['git', 'rev-parse', 'HEAD'], $ref2)
                    && $ref1 === $ref2
                ) {
                    $package['version'] = $this->extendedVersionGuesser->getRootVersionFromEnv();
                }
            }

            $output = '';
            if ('auto' === $reference && is_dir($path . DIRECTORY_SEPARATOR . '.git') && 0 === $this->extendedProcess->execute(array_merge(['git', 'log', '-n1', '--pretty=%H'], GitUtil::getNoShowSignatureFlags($this->extendedProcess)), $output, $path) && is_string($output)) {
                $package['dist']['reference'] = trim($output);
            }

            if (!isset($package['version'])) {
                $versionData = $this->extendedVersionGuesser->guessVersion($package, $path);
                if (is_array($versionData) && $versionData['pretty_version']) {
                    // if there is a feature branch detected, we add a second
                    // package with the feature branch version
                    if (!empty($versionData['feature_pretty_version'])) {
                        $package['version'] = $versionData['feature_pretty_version'];
                        $this->addPackage($this->extendedLoader->load($package));
                    }

                    $package['version'] = $versionData['pretty_version'];
                } else {
                    $package['version'] = 'dev-main';
                }
            }

            try {
                $this->addPackage($this->extendedLoader->load($package));
            } catch (\Exception $e) {
                throw new \RuntimeException('Failed loading the package in ' . $composerFilePath, 0, $e);
            }
        }
    }

    /**
     * Get a list of all (possibly relative) path names matching the configured
     * url (supports globbing). Mirrors the private PathRepository::getUrlMatches().
     *
     * @return string[]
     */
    private function getExtendedUrlMatches(): array
    {
        $flags = GLOB_MARK | GLOB_ONLYDIR;

        if (defined('GLOB_BRACE')) {
            $flags |= GLOB_BRACE;
        } elseif (str_contains($this->extendedUrl, '{') || str_contains($this->extendedUrl, '}')) {
            throw new \RuntimeException('The operating system does not support GLOB_BRACE which is required for the url ' . $this->extendedUrl);
        }

        // Ensure environment-specific path separators are normalized to URL separators
        return array_map(static function ($val): string {
            return rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $val), '/');
        }, glob($this->extendedUrl, $flags) ?: []);
    }
}
