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

namespace SBUERK\ExtendedPathRepository\Tests\Unit\Repository;

use Composer\Config;
use Composer\IO\NullIO;
use Composer\Repository\ArrayRepository;
use Composer\Repository\FilterRepository;
use Composer\Repository\PathRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SBUERK\ExtendedPathRepository\Repository\ExtendedPathRepository;
use SBUERK\ExtendedPathRepository\Repository\RepositoryReplacer;

final class RepositoryReplacerTest extends TestCase
{
    private string $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = dirname(__DIR__, 2) . '/Fixtures';
    }

    #[Test]
    public function plainPathRepositoryIsReplacedWithExtendedOne(): void
    {
        $manager = $this->createManager();
        $manager->addRepository($manager->createRepository('path', $this->pathConfig()));

        self::assertSame(PathRepository::class, $manager->getRepositories()[0]::class);

        $this->upgrade($manager);

        $repositories = $manager->getRepositories();
        self::assertCount(1, $repositories);
        self::assertInstanceOf(ExtendedPathRepository::class, $repositories[0]);
    }

    #[Test]
    public function filterWrappedPathRepositoryHasItsInnerRepositoryReplaced(): void
    {
        $manager = $this->createManager();
        $manager->addRepository($manager->createRepository('path', $this->pathConfig(['only' => ['fixture/pkg-version']])));

        $filter = $manager->getRepositories()[0];
        self::assertInstanceOf(FilterRepository::class, $filter);
        self::assertSame(PathRepository::class, $filter->getRepository()::class);

        $this->upgrade($manager);

        $filter = $manager->getRepositories()[0];
        self::assertInstanceOf(FilterRepository::class, $filter);
        self::assertInstanceOf(ExtendedPathRepository::class, $filter->getRepository());
    }

    #[Test]
    public function alreadyExtendedPathRepositoryIsLeftUntouched(): void
    {
        $manager = $this->createManager();
        $manager->setRepositoryClass('path', ExtendedPathRepository::class);
        $extended = $manager->createRepository('path', $this->pathConfig());
        $manager->addRepository($extended);

        $this->upgrade($manager);

        self::assertSame($extended, $manager->getRepositories()[0]);
    }

    #[Test]
    public function unrelatedRepositoriesAreLeftUntouched(): void
    {
        $manager = $this->createManager();
        $arrayRepository = new ArrayRepository();
        $manager->addRepository($arrayRepository);

        $this->upgrade($manager);

        self::assertSame($arrayRepository, $manager->getRepositories()[0]);
    }

    private function upgrade(RepositoryManager $manager): void
    {
        $manager->setRepositoryClass('path', ExtendedPathRepository::class);
        (new RepositoryReplacer())->replace($manager, new NullIO());
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function pathConfig(array $extra = []): array
    {
        return ['type' => 'path', 'url' => $this->fixtures . '/packages/pkg-version'] + $extra;
    }

    private function createManager(): RepositoryManager
    {
        return RepositoryFactory::manager(new NullIO(), new Config(false, $this->fixtures));
    }
}
