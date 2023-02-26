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

use WeCodeMore\WpPackageAssetsPublisher\Filesystem;

class TestFilesystem extends Filesystem
{
    private string $failCreateDir = 'no';
    private string $failCopy = 'no';
    private string $failSymlink = 'no';
    private string $failRemoveDir = 'no';

    /**
     * @return void
     */
    public function failCreateDir(): void
    {
        $this->failCreateDir = 'yes';
    }

    /**
     * @return void
     */
    public function failCreateDirOnce(): void
    {
        $this->failCreateDir = 'once';
    }

    /**
     * @return void
     */
    public function failCopy(): void
    {
        $this->failCopy = 'yes';
    }

    /**
     * @return void
     */
    public function failCopyOnce(): void
    {
        $this->failCopy = 'once';
    }

    /**
     * @return void
     */
    public function failSymlink(): void
    {
        $this->failSymlink = 'yes';
    }

    /**
     * @return void
     */
    public function failSymlinkOnce(): void
    {
        $this->failSymlink = 'once';
    }

    /**
     * @return void
     */
    public function failRemoveDir(): void
    {
        $this->failRemoveDir = 'yes';
    }

    /**
     * @return void
     */
    public function failRemoveDirOnce(): void
    {
        $this->failRemoveDir = 'once';
    }

    /**
     * @return void
     */
    public function resetFailures(): void
    {
        $this->failCreateDir = 'no';
        $this->failCopy = 'no';
        $this->failSymlink = 'no';
        $this->failRemoveDir = 'no';
    }

    /**
     * @param string $targetPath
     * @return bool
     */
    public function createDir(string $targetPath): bool
    {
        return $this->maybeWrapWithFailure(__FUNCTION__, $targetPath);
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyDir(string $sourcePath, string $targetPath): bool
    {
        return $this->maybeWrapWithFailure(__FUNCTION__, $sourcePath, $targetPath);
    }

    /**
     * @param string $targetPath
     * @param string $linkPath
     * @return bool
     */
    public function symlink(string $targetPath, string $linkPath): bool
    {
        return $this->maybeWrapWithFailure(__FUNCTION__, $targetPath, $linkPath);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function removeDirectory(string $path): bool
    {
        return $this->maybeWrapWithFailure(__FUNCTION__, $path);
    }

    /**
     * @param string $method
     * @param string ...$args
     * @return bool
     */
    private function maybeWrapWithFailure(string $method, string ...$args): bool
    {
        switch ($method) {
            case 'createDir':
                $prop = $this->failCreateDir;
                $propName = 'failCreateDir';
                break;
            case 'copyDir':
                $prop = $this->failCopy;
                $propName = 'failCopy';
                break;
            case 'symlink':
                $prop = $this->failSymlink;
                $propName = 'failSymlink';
                break;
            case 'removeDirectory':
                $prop = $this->failRemoveDir;
                $propName = 'failRemoveDir';
                break;
            default:
                $prop = 'no';
                break;
        }

        if ($prop === 'no') {
            return parent::{$method}(...$args);
        }

        if ($this->{$propName} === 'once') {
            $this->{$propName} = 'no';
        }

        return false;
    }
}
