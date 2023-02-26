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

use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Platform;

/**
 * Wrapper for Composer Filesystem with custom functionalities.
 */
class Filesystem
{
    /**
     * @var ComposerFilesystem
     */
    private ComposerFilesystem $filesystem;

    /**
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(ComposerFilesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path): string
    {
        return $this->filesystem->normalizePath($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isDirEmpty(string $path): bool
    {
        return $this->filesystem->isDirEmpty($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function removeDirectory(string $path): bool
    {
        try {
            return $this->filesystem->removeDirectory($path);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Recursively copy all files from a directory to another.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyDir(string $sourcePath, string $targetPath): bool
    {
        try {
            $sourcePath = realpath($sourcePath);
            if (!$sourcePath || !is_dir($sourcePath)) {
                return false;
            }

            $targetPath = $this->filesystem->normalizePath($targetPath);
            $exists = file_exists($targetPath);
            if ($exists && !is_dir($targetPath)) {
                return false;
            }

            if (!$exists && !$this->createDir($targetPath)) {
                return false;
            }

            $this->filesystem->copy($sourcePath, $targetPath);

            if (!is_dir($targetPath)) {
                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Symlink implementation which uses junction on dirs on Windows.
     *
     * @param string $targetPath
     * @param string $linkPath
     * @return bool
     */
    public function symlink(string $targetPath, string $linkPath): bool
    {
        try {
            if (!file_exists($targetPath)) {
                return false;
            }

            if (file_exists($linkPath) || $this->isLink($linkPath)) {
                $this->filesystem->unlink($linkPath);
            }

            $isWindows = Platform::isWindows();
            $directories = is_dir($targetPath);

            if ($isWindows && $directories) {
                $this->filesystem->junction($targetPath, $linkPath);

                return $this->filesystem->isJunction($linkPath);
            }

            $absolute = $this->filesystem->isAbsolutePath($targetPath)
                && $this->filesystem->isAbsolutePath($linkPath);

            // Attempt relative symlink, but not on Windows
            if ($absolute && !$isWindows) {
                return $this->filesystem->relativeSymlink($targetPath, $linkPath);
            }

            return @symlink($targetPath, $linkPath);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Create a directory recursively, derived from wp_makedir_p.
     *
     * @param string $targetPath
     * @return bool
     */
    public function createDir(string $targetPath): bool
    {
        try {
            $targetPath = $this->filesystem->normalizePath($targetPath);

            if (file_exists($targetPath)) {
                return @is_dir($targetPath);
            }

            $parentDir = dirname($targetPath);
            while ('.' !== $parentDir && !is_dir($parentDir)) {
                $parentDir = dirname($parentDir);
            }

            $stat = @stat($parentDir);
            $permissions = $stat ? $stat['mode'] & 0007777 : 0755;

            if (!@mkdir($targetPath, $permissions, true) && !is_dir($targetPath)) {
                return false;
            }

            if ($permissions !== ($permissions & ~umask())) {
                $nameParts = explode('/', substr($targetPath, strlen($parentDir) + 1) ?: '');
                for ($i = 1, $count = count($nameParts); $i <= $count; $i++) {
                    $dirname = $parentDir . '/' . implode('/', array_slice($nameParts, 0, $i));
                    @chmod($dirname, $permissions);
                }
            }

            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isLink(string $path): bool
    {
        return $this->filesystem->isSymlinkedDirectory($path)
            || $this->filesystem->isJunction($path)
            || is_link($path);
    }
}
