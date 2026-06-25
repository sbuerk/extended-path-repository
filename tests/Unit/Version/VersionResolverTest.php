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

namespace SBUERK\ExtendedPathRepository\Tests\Unit\Version;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SBUERK\ExtendedPathRepository\Version\VersionResolver;

final class VersionResolverTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'epr-version-' . bin2hex(random_bytes(6));
        if (!mkdir($this->workspace) && !is_dir($this->workspace)) {
            self::fail(sprintf('Could not create workspace "%s".', $this->workspace));
        }
    }

    protected function tearDown(): void
    {
        $versionFile = $this->workspace . DIRECTORY_SEPARATOR . 'VERSION';
        if (is_file($versionFile)) {
            unlink($versionFile);
        }
        if (is_dir($this->workspace)) {
            rmdir($this->workspace);
        }
        parent::tearDown();
    }

    #[Test]
    public function composerJsonVersionIsReturnedVerbatimAndWinsOverEverything(): void
    {
        $this->writeVersionFile('5.5.5');
        $package = [
            'version' => '9.9.9',
            'extra' => ['typo3/cms' => ['version' => '1.0.0']],
        ];

        self::assertSame('9.9.9', (new VersionResolver())->resolve($package, $this->workspace));
    }

    #[Test]
    public function extraTypo3CmsVersionIsUsedWhenNoExplicitVersionIsSet(): void
    {
        $this->writeVersionFile('5.5.5');
        $package = ['extra' => ['typo3/cms' => ['version' => '3.1.4']]];

        self::assertSame('3.1.4', (new VersionResolver())->resolve($package, $this->workspace));
    }

    #[Test]
    public function versionFileIsUsedWhenNoVersionAndNoExtraIsSet(): void
    {
        $this->writeVersionFile('4.5.6');

        self::assertSame('4.5.6', (new VersionResolver())->resolve([], $this->workspace));
    }

    #[Test]
    public function firstNonEmptyLineOfVersionFileIsUsed(): void
    {
        $this->writeVersionFile("\n\n  1.2.3  \nignored");

        self::assertSame('1.2.3', (new VersionResolver())->resolve([], $this->workspace));
    }

    #[Test]
    public function nullIsReturnedWhenNoSourceApplies(): void
    {
        self::assertNull((new VersionResolver())->resolve([], $this->workspace));
    }

    #[Test]
    public function invalidExtraVersionFallsThroughToVersionFile(): void
    {
        $this->writeVersionFile('4.5.6');
        $package = ['extra' => ['typo3/cms' => ['version' => 'not-a-version']]];

        self::assertSame('4.5.6', (new VersionResolver())->resolve($package, $this->workspace));
    }

    #[Test]
    public function invalidVersionFileContentIsIgnored(): void
    {
        $this->writeVersionFile('not-a-version');

        self::assertNull((new VersionResolver())->resolve([], $this->workspace));
    }

    /**
     * @param array<string, mixed> $package
     */
    #[Test]
    #[DataProvider('validVersionStringsProvider')]
    public function variousValidVersionStringsAreAccepted(array $package, ?string $versionFile, string $expected): void
    {
        if ($versionFile !== null) {
            $this->writeVersionFile($versionFile);
        }

        self::assertSame($expected, (new VersionResolver())->resolve($package, $this->workspace));
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: ?string, 2: string}>
     */
    public static function validVersionStringsProvider(): iterable
    {
        yield 'semver from version key' => [['version' => '1.2.3'], null, '1.2.3'];
        yield 'dev branch from version key' => [['version' => 'dev-main'], null, 'dev-main'];
        yield 'v-prefixed extra version' => [['extra' => ['typo3/cms' => ['version' => 'v12.4.0']]], null, 'v12.4.0'];
        yield 'four-part extra version' => [['extra' => ['typo3/cms' => ['version' => '12.4.0.0']]], null, '12.4.0.0'];
        yield 'version file with stability' => [[], '1.0.0-beta1', '1.0.0-beta1'];
    }

    private function writeVersionFile(string $contents): void
    {
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'VERSION', $contents);
    }
}
