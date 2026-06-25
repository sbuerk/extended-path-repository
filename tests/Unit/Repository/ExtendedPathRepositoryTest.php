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
use Composer\Package\PackageInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SBUERK\ExtendedPathRepository\Repository\ExtendedPathRepository;

final class ExtendedPathRepositoryTest extends TestCase
{
    private string $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = dirname(__DIR__, 2) . '/Fixtures';
    }

    #[Test]
    public function explicitComposerJsonVersionIsUsed(): void
    {
        self::assertSame('2.0.0', $this->resolveSingleVersion('packages/pkg-version'));
    }

    #[Test]
    public function extraTypo3CmsVersionIsUsed(): void
    {
        self::assertSame('3.1.4', $this->resolveSingleVersion('packages/pkg-extra'));
    }

    #[Test]
    public function siblingVersionFileIsUsed(): void
    {
        self::assertSame('4.5.6', $this->resolveSingleVersion('packages/pkg-versionfile'));
    }

    #[Test]
    public function explicitVersionWinsOverExtraAndVersionFile(): void
    {
        self::assertSame('9.9.9', $this->resolveSingleVersion('packages/pkg-precedence'));
    }

    #[Test]
    public function extraVersionWinsOverVersionFile(): void
    {
        self::assertSame('6.6.6', $this->resolveSingleVersion('packages/pkg-extra-and-versionfile'));
    }

    #[Test]
    public function stockVersionsOptionStillAppliesWhenNoExtendedSourceMatches(): void
    {
        self::assertSame(
            '7.7.7',
            $this->resolveSingleVersion('packages/pkg-options', ['versions' => ['fixture/pkg-options' => '7.7.7']])
        );
    }

    #[Test]
    public function extendedDetectionWinsOverStockVersionsOption(): void
    {
        self::assertSame(
            '3.1.4',
            $this->resolveSingleVersion('packages/pkg-extra', ['versions' => ['fixture/pkg-extra' => '0.0.1']])
        );
    }

    #[Test]
    public function globPatternMatchingMultipleDirectoriesRegistersEveryPackage(): void
    {
        $repository = $this->createRepository('multi/*');

        $versions = [];
        foreach ($repository->getPackages() as $package) {
            $versions[$package->getName()] = $package->getPrettyVersion();
        }
        ksort($versions);

        self::assertSame(
            ['fixture/package-a' => '1.0.0', 'fixture/package-b' => '2.0.0'],
            $versions
        );
    }

    #[Test]
    public function nonMatchingGlobPatternIsSilentlyIgnored(): void
    {
        $repository = $this->createRepository('packages/does-not-exist-*/*');

        self::assertSame([], $repository->getPackages());
    }

    #[Test]
    public function missingFixedPathIsSilentlyIgnored(): void
    {
        $repository = $this->createRepository('this/path/does/not/exist');

        self::assertSame([], $repository->getPackages());
    }

    #[Test]
    public function directoryWithoutComposerJsonIsSilentlyIgnored(): void
    {
        $repository = $this->createRepository('empty-dir/*');

        self::assertSame([], $repository->getPackages());
    }

    /**
     * @param array{versions?: array<string, string>} $options
     */
    private function resolveSingleVersion(string $relativeUrl, array $options = []): string
    {
        $packages = $this->createRepository($relativeUrl, $options)->getPackages();
        self::assertCount(1, $packages, sprintf('Expected exactly one package for "%s".', $relativeUrl));

        $package = $packages[0];
        self::assertInstanceOf(PackageInterface::class, $package);

        return $package->getPrettyVersion();
    }

    /**
     * @param array{versions?: array<string, string>} $options
     */
    private function createRepository(string $relativeUrl, array $options = []): ExtendedPathRepository
    {
        $config = ['url' => $this->fixtures . '/' . $relativeUrl];
        if ($options !== []) {
            $config['options'] = $options;
        }

        return new ExtendedPathRepository($config, new NullIO(), new Config(false, $this->fixtures));
    }
}
