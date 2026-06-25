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

namespace SBUERK\ExtendedPathRepository;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use SBUERK\ExtendedPathRepository\Repository\ExtendedPathRepository;
use SBUERK\ExtendedPathRepository\Repository\RepositoryReplacer;

/**
 * Composer plugin entry point.
 *
 * On activation it makes composer's `path` repository type use
 * {@see ExtendedPathRepository} instead of the stock implementation, both for
 * repositories created from this point on and for the ones already instantiated
 * from the root `composer.json` (see {@see RepositoryReplacer}).
 */
final class Plugin implements PluginInterface
{
    private RepositoryReplacer $repositoryReplacer;

    public function __construct(?RepositoryReplacer $repositoryReplacer = null)
    {
        $this->repositoryReplacer = $repositoryReplacer ?? new RepositoryReplacer();
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $repositoryManager = $composer->getRepositoryManager();

        // Any `path` repository created from now on uses the extended class.
        $repositoryManager->setRepositoryClass('path', ExtendedPathRepository::class);

        // Root composer.json repositories are created before plugins activate,
        // so upgrade the already-instantiated ones in place.
        $this->repositoryReplacer->replace($repositoryManager, $io);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No persistent state to tear down.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No persistent state to remove.
    }
}
