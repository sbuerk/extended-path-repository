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

use Composer\IO\IOInterface;
use Composer\Repository\FilterRepository;
use Composer\Repository\PathRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;

/**
 * Replaces already-instantiated stock {@see PathRepository} instances inside a
 * {@see RepositoryManager} with {@see ExtendedPathRepository} instances.
 *
 * This is required because repositories declared in the root `composer.json` are
 * created during the root package load, which happens *before* composer plugins
 * are activated. Registering the repository class via
 * {@see RepositoryManager::setRepositoryClass()} only affects repositories
 * created afterwards, so the already-created ones must be swapped in place.
 *
 * The manager exposes no public API to replace a repository, hence a minimal,
 * defensive use of reflection. Should composer's internals change in an
 * incompatible way, the replacement degrades gracefully: a verbose warning is
 * emitted and the stock path repositories keep working unchanged.
 */
final class RepositoryReplacer
{
    /**
     * @param RepositoryManager $repositoryManager the manager whose repositories should be upgraded
     */
    public function replace(RepositoryManager $repositoryManager, IOInterface $io): void
    {
        $repositoriesProperty = $this->accessProperty(RepositoryManager::class, 'repositories', $io);
        if ($repositoriesProperty === null) {
            return;
        }

        /** @var array<int, RepositoryInterface> $repositories */
        $repositories = $repositoriesProperty->getValue($repositoryManager);
        $changed = false;

        foreach ($repositories as $index => $repository) {
            if ($repository instanceof FilterRepository) {
                $inner = $repository->getRepository();
                if ($this->isPlainPathRepository($inner)) {
                    $replacement = $repositoryManager->createRepository('path', $this->repoConfig($inner));
                    $changed = $this->replaceFilterInner($repository, $replacement, $io) || $changed;
                }
                continue;
            }

            if ($this->isPlainPathRepository($repository)) {
                $repositories[$index] = $repositoryManager->createRepository('path', $this->repoConfig($repository));
                $changed = true;
            }
        }

        if ($changed) {
            $repositoriesProperty->setValue($repositoryManager, array_values($repositories));
        }
    }

    /**
     * Only swap *exact* stock path repositories; never touch subclasses (which
     * may already be the extended one, or a third-party specialisation).
     *
     * @phpstan-assert-if-true PathRepository $repository
     */
    private function isPlainPathRepository(RepositoryInterface $repository): bool
    {
        return $repository::class === PathRepository::class;
    }

    /**
     * @return array<string, mixed>
     */
    private function repoConfig(PathRepository $repository): array
    {
        /** @var array<string, mixed> $config */
        $config = $repository->getRepoConfig();

        return $config;
    }

    private function replaceFilterInner(FilterRepository $filterRepository, RepositoryInterface $replacement, IOInterface $io): bool
    {
        $property = $this->accessProperty(FilterRepository::class, 'repo', $io);
        if ($property === null) {
            return false;
        }

        $property->setValue($filterRepository, $replacement);

        return true;
    }

    /**
     * @param class-string $class
     */
    private function accessProperty(string $class, string $property, IOInterface $io): ?\ReflectionProperty
    {
        try {
            // Note: ReflectionProperty grants access to private members without
            // setAccessible() since PHP 8.1 (and the method is deprecated in 8.5).
            return new \ReflectionProperty($class, $property);
        } catch (\ReflectionException $exception) {
            $io->writeError(
                sprintf(
                    '<warning>extended-path-repository: could not access %s::$%s (%s); existing path repositories keep the stock behaviour.</warning>',
                    $class,
                    $property,
                    $exception->getMessage()
                ),
                true,
                IOInterface::VERBOSE
            );

            return null;
        }
    }
}
