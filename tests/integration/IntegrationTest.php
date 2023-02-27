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

/**
 * @covers \WeCodeMore\WpPackageAssetsPublisher\Publisher
 */
class IntegrationTest extends IntegrationCase
{
    /**
     * @return void
     */
    public function testCopyOnLinkFailure(): void
    {
        $package = $this->loadPackage('base');
        $publisher = $this->factoryPublisher();

        $this->filesystem->failSymlink();

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/base');

        static::assertTrue($this->io->hasErrorThatMatches('~failed.+public/.+?symlink.+?attempting~i'));
        static::assertTrue($this->io->hasErrorThatMatches('~failed.+images/.+?symlink.+?attempting~i'));
        static::assertTrue($this->io->hasOutputThatMatches('~published assets.+?images/~i'));
        static::assertTrue($this->io->hasOutputThatMatches('~published assets.+?public/~i'));
        static::assertTrue(is_file("{$expectedPath}/images/image.gif"));
        static::assertTrue(is_file("{$expectedPath}/public/main.js"));
    }

    /**
     * @return void
     */
    public function testLinkDisabledDoNothingIfCopyFail(): void
    {
        $package = $this->loadPackage('no-link');
        $publisher = $this->factoryPublisher();

        $this->filesystem->failCopy();

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/no-link');

        static::assertFalse($this->io->hasErrorThatMatches('/symlink/i'));
        static::assertTrue($this->io->hasErrorThatMatches('/failed.+?copy.+?assets/i'));
        static::assertFalse(is_file("{$expectedPath}/public/main.js"));
    }

    /**
     * @return void
     */
    public function testForcedLinkDisabledDoNothingIfLinkFail(): void
    {
        $package = $this->loadPackage('forced-link');
        $publisher = $this->factoryPublisher();

        $this->filesystem->failSymlink();
        $this->filesystem->failCopy();

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/forced-link');

        static::assertFalse($this->io->hasErrorThatMatches('/failed.+?symlink.+?attempting/i'));
        static::assertTrue($this->io->hasErrorThatMatches('/failed.+?symlink/i'));
        static::assertFalse($this->io->hasErrorThatMatches('/failed.+?copy.+assets/i'));
        static::assertFalse(is_file("{$expectedPath}/public/main.js"));
    }

    /**
     * @return void
     */
    public function testForcedCopyOnRootFailFailHardIfPackageIsStrict(): void
    {
        $package = $this->loadPackage('strict');
        $publisher = $this->factoryPublisher(null, false, false);

        $this->filesystem->failSymlink();
        $this->filesystem->failCopy();

        $failure = '';
        try {
            $publisher->publish($package);
        } catch (\Error $error) {
            $failure = $error->getMessage();
        }

        $expectedPath = $this->outputPath('.published-package-assets/test/strict');

        static::assertFalse($this->io->hasErrors());
        static::assertNotFalse(preg_match('/failed.+?copy.+assets/i', $failure));
        static::assertFalse(is_file("{$expectedPath}/public/main.js"));
    }

    /**
     * @return void
     */
    public function testPublishThenUnpublish(): void
    {
        $package = $this->loadPackage('base');
        $publisher = $this->factoryPublisher(null, true, false);

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/base');

        static::assertTrue($this->io->hasOutputThatMatches('~published assets.+?images/~i'));
        static::assertTrue($this->io->hasOutputThatMatches('~published assets.+?public/~i'));
        static::assertFalse($this->io->hasErrors());

        static::assertTrue(is_file("{$expectedPath}/images/image.gif"));
        static::assertTrue(is_file("{$expectedPath}/public/main.js"));

        $this->io->resetAllTestWrites();

        $publisher->unpublish($package);

        static::assertTrue($this->io->hasOutputThatMatches('~unpublished.+?assets.+?images/~i'));
        static::assertTrue($this->io->hasOutputThatMatches('~unpublished.+?assets.+?public/~i'));
        static::assertFalse($this->io->hasErrors());

        static::assertFalse(is_file("{$expectedPath}/images/image.gif"));
        static::assertFalse(is_file("{$expectedPath}/public/main.js"));
        static::assertFalse(is_dir($this->outputPath('.published-package-assets/test/base')));
        static::assertFalse(is_dir($this->outputPath('.published-package-assets/test')));
        static::assertFalse(is_dir($this->outputPath('.published-package-assets')));
    }

    /**
     * @return void
     */
    public function testPublishThenUnpublishWithOneFailure(): void
    {
        $package = $this->loadPackage('base');
        $publisher = $this->factoryPublisher(null, true, false);

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/base');
        static::assertFalse($this->io->hasErrors());

        static::assertTrue(is_file("{$expectedPath}/images/image.gif"));
        static::assertTrue(is_file("{$expectedPath}/public/main.js"));

        $this->io->resetAllTestWrites();

        $this->filesystem->failRemoveDirOnce();

        $publisher->unpublish($package);

        static::assertTrue($this->io->hasOutputThatMatches('~unpublished.+?assets~i'));
        static::assertTrue($this->io->hasErrorThatMatches('~failed.+removing~i'));
        static::assertTrue(is_dir($this->outputPath('.published-package-assets/test/base')));

        [$exists, $notExists] = $this->io->hasOutputThatMatches('~unpublished.+?assets.+?images/~i')
            ? ['public/main.js', 'images/image.gif']
            : ['images/image.gif', 'public/main.js'];

        static::assertFalse(is_file("{$expectedPath}/{$notExists}"));
        static::assertTrue(is_file("{$expectedPath}/{$exists}"));
    }

    /**
     * @return void
     */
    public function testCustomPackage(): void
    {
        $package = $this->loadPackage('custom-type');
        $publisher = $this->factoryPublisher(null, true, false, null, ['wp-library']);

        $publisher->publish($package);

        $expectedPath = $this->outputPath('.published-package-assets/test/custom-type');

        static::assertTrue($this->io->hasOutputThatMatches('~published assets.+?public/~i'));
        static::assertTrue(is_file("{$expectedPath}/public/main.js"));
    }
}
