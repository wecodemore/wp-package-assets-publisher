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
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class Publisher
{
    private InstallationManager $installationManager;
    private IOInterface $io;
    private Filesystem $filesystem;
    private Config $config;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param Config $config
     */
    public function __construct(
        InstallationManager $installationManager,
        IOInterface $io,
        Filesystem $filesystem,
        Config $config
    ) {

        $this->installationManager = $installationManager;
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * @param PackageInterface $package
     * @return void
     */
    public function publish(PackageInterface $package): void
    {
        $paths = $this->config->relativeTargetPaths($package);

        if ($paths === null) {
            $name = $package->getPrettyName();
            $noAssetsFormat = '- "%s" has no assets to publish.';
            $this->io->write(sprintf($noAssetsFormat, $name), true, IOInterface::VERBOSE);

            return;
        }

        $assetsPath = $this->config->assetsPath();
        $isStrict = $this->config->isStrict($package);

        if ($assetsPath === null) {
            $strict = $this->config->isStrict(null) || $isStrict;
            $this->printError($package, null, 'invalid root package configuration', $strict);

            return;
        }

        if ($paths === []) {
            $this->printError($package, null, 'invalid paths configuration', $isStrict);

            return;
        }

        $installPath = $this->installationManager->getInstallPath($package);
        $basePath = $this->filesystem->normalizePath($installPath);

        foreach ($paths as $path) {
            $this->publishPath($path, $basePath, $assetsPath, $package, $isStrict);
        }
    }

    /**
     * @param PackageInterface $package
     * @return void
     */
    public function unpublish(PackageInterface $package): void
    {
        $assetsPath = $this->config->assetsPath();
        if (($assetsPath === null) || !is_dir($assetsPath)) {
            return;
        }

        $paths = $this->config->relativeTargetPaths($package);
        $pkgTarget = $this->filesystem->normalizePath("{$assetsPath}/" . $package->getPrettyName());

        foreach ($paths ?? [] as $path) {
            $fullPath = "{$pkgTarget}/{$path}";
            if (!$this->filesystem->isLink($fullPath) && !is_dir($fullPath)) {
                continue;
            }
            if (!$this->filesystem->removeDirectory($fullPath)) {
                $this->printError(
                    $package,
                    $path,
                    "failed removing '{$fullPath}'",
                    false,
                    -1
                );
                continue;
            }

            $this->printSuccess($package, $path, -1);
        }

        $toEmpty = [$pkgTarget, dirname($pkgTarget), dirname($pkgTarget, 2)];
        foreach ($toEmpty as $dir) {
            $this->filesystem->isDirEmpty($dir) and $this->filesystem->removeDirectory($dir);
        }
    }

    /**
     * @param string $path
     * @param string $basePath
     * @param string $assetsPath
     * @param PackageInterface $package
     * @param bool $isStrict
     * @return void
     */
    private function publishPath(
        string $path,
        string $basePath,
        string $assetsPath,
        PackageInterface $package,
        bool $isStrict
    ) {

        $linksPath = $this->filesystem->normalizePath("{$assetsPath}/" . $package->getPrettyName());
        $isSymlink = $this->config->isSymlink($package);
        $forceSymlink = $isSymlink === true;
        $allowSymlink = $isSymlink !== false;

        $sourcePath = $this->filesystem->normalizePath("{$basePath}/{$path}");
        if (!is_dir($sourcePath)) {
            $this->printError($package, $path, "invalid source directory", $isStrict);

            return;
        }

        if (!$this->filesystem->createDir($linksPath)) {
            $this->printError($package, $path, "could not create '{$linksPath}'", $isStrict);

            return;
        }

        if ($allowSymlink && $this->filesystem->symlink($sourcePath, "{$linksPath}/{$path}")) {
            $this->printSuccess($package, $path);

            return;
        }

        if ($allowSymlink) {
            $message = "failed creating symlink at '{$linksPath}/{$path}'";
            $this->printError(
                $package,
                $path,
                $forceSymlink ? $message : "{$message}, attempting copy",
                $isStrict && $forceSymlink
            );
            if ($forceSymlink) {
                return;
            }
        }

        if ($this->filesystem->copyDir($sourcePath, "{$linksPath}/{$path}")) {
            $this->printSuccess($package, $path);

            return;
        }

        $this->printError(
            $package,
            $path,
            "could not copy assets in '{$linksPath}/{$path}'",
            $isStrict
        );
    }

    /**
     * @param PackageInterface $package
     * @param string|null $path
     * @param string $message
     * @param bool $strict
     * @param int $operation
     * @return void
     */
    private function printError(
        PackageInterface $package,
        ?string $path,
        string $message,
        bool $strict,
        int $operation = 1
    ): void {

        $name = $package->getName();
        $message = sprintf(
            '  - Failed %s assets%s for <fg=green>%s</>: %s.',
            ($operation > 0) ? 'publishing' : 'unpublishing',
            ($path === null) ? '' : sprintf(' path <fg=yellow>"%s"</>', trim($path, '/\\') . '/'),
            $name,
            $message
        );

        if ($strict) {
            throw new \Error($message);
        }

        $this->io->writeError($message);
    }

    /**
     * @param PackageInterface $package
     * @param string $path
     * @param int $operation
     * @return void
     */
    private function printSuccess(PackageInterface $package, string $path, int $operation = 1): void
    {
        $name = $package->getPrettyName();
        $opStr = ($operation > 0) ? 'Published' : 'Unpublished';
        $this->io->write(
            sprintf(
                '  - %s assets path <fg=yellow>"%s"</> for <fg=green>%s</>.',
                $opStr,
                trim($path, '/\\') . '/',
                $name
            )
        );
    }
}
