<?php

/*
 * This file is part of the "wordpress-package-assets-publisher" package.
 *
 * Copyright (C) 2023 Inpsyde GmbH
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

declare(strict_types=1);

namespace WeCodeMore\WpPackageAssetsPublisher\Tests;

use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem as ComposerFilesystem;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WeCodeMore\WpPackageAssetsPublisher\Filesystem;
use WeCodeMore\WpPackageAssetsPublisher\Config;
use WeCodeMore\WpPackageAssetsPublisher\Publisher;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    public ?TestFilesystem $filesystem = null;
    public ?TestIo $io = null;

    /**
     * @param mixed $path1
     * @param mixed $path2
     * @return void
     */
    protected static function assertSamePaths($path1, $path2): void
    {
        static::assertTrue(is_string($path1));
        static::assertTrue(is_string($path2));
        static::assertSame(str_replace('\\', '/', $path1), str_replace('\\', '/', $path2));
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->startMockery();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem = null;
        $this->io = null;
        $this->closeMockery();
        parent::tearDown();
    }

    /**
     * @param string|null $package
     * @return string
     */
    protected function packagesPath(?string $package = null): string
    {
        $path = getenv('TESTS_FIXTURES_PATH') . '/packages';
        $package and $path .= "/{$package}";

        return $path;
    }

    /**
     * @param string|null $package
     * @return string
     */
    protected function outputPath(string $relative = ''): string
    {
        return getenv('TESTS_FIXTURES_PATH') . '/output/' . ltrim($relative, '/');
    }

    /**
     * @param array $extra
     * @param string $name
     * @return PackageInterface
     */
    protected function factoryPackage(
        array $extra = [],
        ?string $name = null
    ): PackageInterface {

        $package = new CompletePackage($name ?? 'test/base', '1', '1.0.0.0');
        $package->setExtra($extra);

        return $package;
    }

    /**
     * @param int $verbosity
     * @return IOInterface
     */
    protected function factoryIo(int $verbosity = IOInterface::NORMAL): IOInterface
    {
        $this->io or $this->io = new TestIo($verbosity);

        return $this->io;
    }

    /**
     * @return Filesystem
     */
    protected function factoryFilesystem(): Filesystem
    {
        $this->filesystem or $this->filesystem = new TestFilesystem(new ComposerFilesystem());

        return $this->filesystem;
    }

    /**
     * @param array $extra
     * @return Config
     */
    protected function factoryConfig(array $extra = []): Config
    {
        return new Config($extra, $this->factoryFilesystem());
    }

    /**
     * @param string|null $dir
     * @param bool $strict
     * @param bool|null $symlink
     * @param string|null $targetBase
     * @param array $types
     * @return Publisher
     */
    protected function factoryPublisher(
        ?string $dir = null,
        bool $strict = false,
        ?bool $symlink = null,
        ?string $targetBase = null,
        array $types = []
    ): Publisher {

        $extra = [
            'package-assets-publisher' => [
                'publish-dir' => ($dir ?? $this->outputPath()),
                'strict' => $strict,
                'symlink' => $symlink,
                'types' => $types,
            ],
        ];

        return new Publisher(
            $this->factoryInstallationManager($targetBase),
            $this->factoryIo(),
            $this->factoryFilesystem(),
            $this->factoryConfig($extra)
        );
    }

    /**
     * @param string|null $targetBase
     * @return InstallationManager
     */
    protected function factoryInstallationManager(?string $targetBase = null): InstallationManager
    {
        $targetBase = rtrim($targetBase ?? $this->packagesPath(), '/\\') . '/';
        $manager = \Mockery::mock(InstallationManager::class);
        $manager->allows('getInstallPath')->andReturnUsing(
            static function (PackageInterface $package) use ($targetBase): string {
                return $targetBase . explode('/', $package->getPrettyName())[1];
            }
        );

        return $manager;
    }
}
