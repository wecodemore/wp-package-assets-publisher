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

namespace WeCodeMore\WpPackageAssetsPublisher;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem as ComposerFilesystem;
use React\Promise\PromiseInterface;

class Installer extends LibraryInstaller
{
    private const PACKAGE_TYPE = 'wordpress-package';

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     */
    public function __construct(IOInterface $io, Composer $composer)
    {
        parent::__construct($io, $composer, self::PACKAGE_TYPE);

        $manager = $composer->getInstallationManager();
        $filesystem = new Filesystem(new ComposerFilesystem());
        $config = new Config($composer->getPackage()->getExtra(), $filesystem);

        $this->publisher = new Publisher($manager, $io, $filesystem, $config);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     * @return PromiseInterface|null
     *
     * @see Publisher::publish()
     */
    public function install(
        InstalledRepositoryInterface $repo,
        PackageInterface $package
    ): ?PromiseInterface {

        return $this->appendCallback(parent::install($repo, $package), $package, 'publish');
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $initial
     * @param PackageInterface $target
     * @return PromiseInterface|null
     *
     * @see Publisher::publish()
     */
    public function update(
        InstalledRepositoryInterface $repo,
        PackageInterface $initial,
        PackageInterface $target
    ): ?PromiseInterface {

        return $this->appendCallback(parent::update($repo, $initial, $target), $target, 'publish');
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     * @return PromiseInterface|null
     *
     * @see Publisher::unpublish()
     */
    public function uninstall(
        InstalledRepositoryInterface $repo,
        PackageInterface $package
    ): ?PromiseInterface {

        return $this->appendCallback(parent::uninstall($repo, $package), $package, 'unpublish');
    }

    /**
     * @param mixed $promise
     * @param PackageInterface $package
     * @param "publish"|"unpublish" $method
     * @return PromiseInterface|null
     */
    private function appendCallback(
        $promise,
        PackageInterface $package,
        string $method
    ): ?PromiseInterface {

        if ($promise instanceof PromiseInterface) {
            /** @psalm-suppress MissingClosureReturnType */
            return $promise->then(fn() => $this->publisher->{$method}($package));
        }

        return null;
    }
}
