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

use const WeCodeMore\WpPackageAssetsPublisher\ASSETS_PATH_NAME;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function testParseRootOnlyPath(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'publish-dir' => __DIR__,
            ],
        ]);

        static::assertSamePaths(__DIR__ . '/' . ASSETS_PATH_NAME, $config->assetsPath());
        static::assertFalse($config->isStrict(null));
        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootOnlyByWordPressContent(): void
    {
        $config = $this->factoryConfig([
            'wordpress-content-dir' => __DIR__,
            'package-assets-publisher' => [],
        ]);

        static::assertSamePaths(__DIR__ . '/plugins/' . ASSETS_PATH_NAME, $config->assetsPath());
        static::assertFalse($config->isStrict(null));
        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootInvalidPath(): void
    {
        $config = $this->factoryConfig([
            'wordpress-content-dir' => __DIR__,
            'package-assets-publisher' => [
                'publish-dir' => [],
            ],
        ]);

        static::assertNull($config->assetsPath());
    }

    /**
     * @test
     */
    public function testParseRootStrictTrue(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => true,
            ],
        ]);

        static::assertTrue($config->isStrict(null));
    }

    /**
     * @test
     */
    public function testParseRootStrictFalse(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => false,
            ],
        ]);

        static::assertFalse($config->isStrict(null));
    }

    /**
     * @test
     */
    public function testParseRootStrictTrueFiltered(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => 'yes',
            ],
        ]);

        static::assertTrue($config->isStrict(null));
    }

    /**
     * @test
     */
    public function testParseRootStrictFalseFiltered(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => 'off',
            ],
        ]);

        static::assertFalse($config->isStrict(null));
    }

    /**
     * @test
     */
    public function testParseRootStrictFalseOnInvalid(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => __DIR__,
            ],
        ]);

        static::assertFalse($config->isStrict(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkTrue(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => true,
            ],
        ]);

        static::assertTrue($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkFalse(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => false,
            ],
        ]);

        static::assertFalse($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkTrueFiltered(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => 'on',
            ],
        ]);

        static::assertTrue($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkFalseFiltered(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => '0',
            ],
        ]);

        static::assertFalse($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkNullIfEmpty(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => '  ',
            ],
        ]);

        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkNullIfNull(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => null,
            ],
        ]);

        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testParseRootSymlinkNullIfNullIfNotScalar(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'symlink' => [],
            ],
        ]);

        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testPackageNoConfig(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage();

        static::assertNull($config->relativeTargetPaths($package));
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageInvalidConfigString(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => ' ']);

        static::assertSame([], $config->relativeTargetPaths($package));
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageInvalidConfigBool(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => true]);

        static::assertSame([], $config->relativeTargetPaths($package));
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageInvalidConfigBadPathArray(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => [true, '', ' ', 1]]);

        static::assertSame([], $config->relativeTargetPaths($package));
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageOnlyPathsAsArray(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => ['./web\\public//']]);

        static::assertSame($config->relativeTargetPaths($package), ['web/public']);
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageOnlyPathsAsString(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => './web\\public//']);

        static::assertSame($config->relativeTargetPaths($package), ['web/public']);
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageOnlyPathsAsObject(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage([
            'package-assets' => [
                'paths' => ['./web\\public//', 'images//'],
            ],
        ]);

        static::assertSame($config->relativeTargetPaths($package), ['web/public', 'images']);
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageOnlyPathsAsObjectWithStringPath(): void
    {
        $config = $this->factoryConfig(['wordpress-content-dir' => __DIR__]);
        $package = $this->factoryPackage(['package-assets' => ['paths' => 'images//']]);

        static::assertSame($config->relativeTargetPaths($package), ['images']);
        static::assertFalse($config->isStrict($package));
        static::assertNull($config->isSymlink($package));
    }

    /**
     * @test
     */
    public function testPackageOverride(): void
    {
        $config = $this->factoryConfig([
            'package-assets-publisher' => [
                'strict' => true,
                'symlink' => false,
            ],
        ]);
        $package = $this->factoryPackage([
            'package-assets' => [
                'paths' => ['public'],
                'options' => [
                    'strict' => false,
                    'symlink' => true,
                ],
            ],
        ]);

        static::assertSame($config->relativeTargetPaths($package), ['public']);
        static::assertTrue($config->isStrict(null));
        static::assertFalse($config->isStrict($package));
        static::assertTrue($config->isSymlink($package));
        static::assertFalse($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testPackageOverrideFromDefaults(): void
    {
        $config = $this->factoryConfig();
        $package = $this->factoryPackage([
            'package-assets' => [
                'paths' => ['public'],
                'options' => [
                    'strict' => true,
                    'symlink' => false,
                ],
            ],
        ]);

        static::assertSame($config->relativeTargetPaths($package), ['public']);
        static::assertFalse($config->isStrict(null));
        static::assertTrue($config->isStrict($package));
        static::assertFalse($config->isSymlink($package));
        static::assertNull($config->isSymlink(null));
    }

    /**
     * @test
     */
    public function testPackageOverrideWhenInvalidPath(): void
    {
        $config = $this->factoryConfig();
        $package = $this->factoryPackage([
            'package-assets' => [
                'options' => [
                    'strict' => true,
                    'symlink' => false,
                ],
            ],
        ]);

        static::assertSame([], $config->relativeTargetPaths($package));
        static::assertFalse($config->isStrict(null));
        static::assertTrue($config->isStrict($package));
        static::assertFalse($config->isSymlink($package));
        static::assertNull($config->isSymlink(null));
    }
}
