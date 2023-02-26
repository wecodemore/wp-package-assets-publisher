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

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class PublisherTest extends TestCase
{
    /**
     * @test
     */
    public function testPackageNoConfigDoesNothingOnPublish(): void
    {
        $publisher = $this->factoryPublisher();
        $this->io->verbosity = IOInterface::VERBOSE;

        $publisher->publish($this->factoryPackage());

        static::assertTrue($this->io->hasOutputThatMatches('/no assets/i'));
    }

    /**
     * @test
     */
    public function testFailsIfNoAssetsPathNoStrict(): void
    {
        $publisher = $this->factoryPublisher('');
        $publisher->publish($this->factoryAssetsPackage());

        static::assertTrue($this->io->hasErrorThatMatches('/invalid root/i'));
    }

    /**
     * @test
     */
    public function testFailsIfNoAssetsPathStrict(): void
    {
        $publisher = $this->factoryPublisher('');

        try {
            $publisher->publish($this->factoryAssetsPackage());
            throw new \Error('Expected error did not happen.');
        } catch (\Error $error) {
            static::assertNotFalse(preg_match('/invalid root/i', $error->getMessage()));
        }
    }

    /**
     * @test
     */
    public function testFailsIfInvalidPaths(): void
    {
        $publisher = $this->factoryPublisher();
        $publisher->publish($this->factoryAssetsPackage(null, null, [1]));

        static::assertTrue($this->io->hasErrorThatMatches('/invalid paths/i'));
    }

    /**
     * @test
     */
    public function testFailsIfInvalidPathsStrict(): void
    {
        $publisher = $this->factoryPublisher();

        try {
            $publisher->publish($this->factoryAssetsPackage(true, null, [1]));
            throw new \Error('Expected error did not happen.');
        } catch (\Error $error) {
            static::assertNotFalse(preg_match('/invalid paths/i', $error->getMessage()));
        }
    }

    /**
     * @test
     */
    public function testFailsIfSourcePathDoesNotExists(): void
    {
        $publisher = $this->factoryPublisher(null, false, null, __DIR__);
        $publisher->publish($this->factoryAssetsPackage());

        static::assertTrue($this->io->hasErrorThatMatches('/invalid source/i'));
    }

    /**
     * @test
     */
    public function testFailsIfSourcePathDoesNotExistsStrict(): void
    {
        $publisher = $this->factoryPublisher(null, true, null, __DIR__);

        try {
            $publisher->publish($this->factoryAssetsPackage());
            throw new \Error('Expected error did not happen.');
        } catch (\Error $error) {
            static::assertNotFalse(preg_match('/invalid source/i', $error->getMessage()));
        }
    }

    /**
     * @test
     */
    public function testFailsIfCreateDirFails(): void
    {
        $publisher = $this->factoryPublisher();

        $this->filesystem->failCreateDirOnce();

        $publisher->publish($this->factoryAssetsPackage());

        $expectedPath = preg_quote($this->outputPath('.published-package-assets/test/base'), '~');

        static::assertTrue($this->io->hasErrorThatMatches("~not.+?create.+?{$expectedPath}~i"));
    }

    /**
     * @test
     */
    public function testFailsIfCreateDirFailsStrict(): void
    {
        $publisher = $this->factoryPublisher();
        $this->filesystem->failCreateDirOnce();

        $expectedPath = preg_quote($this->outputPath('.published-package-assets/test/base'), '~');

        try {
            $publisher->publish($this->factoryAssetsPackage(true));
            throw new \Error('Expected error did not happen.');
        } catch (\Error $error) {
            static::assertNotFalse(
                preg_match("~not.+?create.+?{$expectedPath}~i", $error->getMessage())
            );
        }
    }

    /**
     * @param bool|null $strict
     * @param bool|null $symlink
     * @param array $paths
     * @param string|null $name
     * @return PackageInterface
     */
    private function factoryAssetsPackage(
        ?bool $strict = null,
        ?bool $symlink = null,
        array $paths = ['public'],
        ?string $name = null
    ): PackageInterface {

        $options = [];
        ($strict !== null) and $options['strict'] = $strict;
        ($symlink !== null) and $options['symlink'] = $symlink;
        $config = compact('paths', 'options');

        return $this->factoryPackage(['package-assets' => $config], $name);
    }
}
