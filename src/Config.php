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

use Composer\Package\PackageInterface;

/**
 * @psalm-type Package-Config = list{ list<non-empty-string>|null, bool, bool|null }
 */
class Config
{
    private const EXTRA_KEY_PACKAGE = 'package-assets';
    private const EXTRA_KEY_ROOT = 'package-assets-publisher';
    private const KEY_PUBLISH_DIR = 'publish-dir';
    private const KEY_CONTENT_DIR = 'wordpress-content-dir';
    private const KEY_PATHS = 'paths';
    private const KEY_OPTIONS = 'options';
    private const KEY_SYMLINK = 'symlink';
    private const KEY_STRICT = 'strict';

    private Filesystem $filesystem;
    /** @var \SplObjectStorage<PackageInterface, Package-Config> */
    private \SplObjectStorage $packages;
    private ?string $assetsPath = null;
    private ?bool $isSymlink = null;
    private bool $isStrict = false;

    /**
     * @param array $extra
     * @param Filesystem $filesystem
     */
    public function __construct(array $extra, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->packages = new \SplObjectStorage();
        $this->parseRoot($extra);
    }

    /**
     * @return string|null
     */
    public function assetsPath(): ?string
    {
        return $this->assetsPath;
    }

    /**
     * @param PackageInterface $package
     * @return list<non-empty-string>|null
     */
    public function relativeTargetPaths(PackageInterface $package): ?array
    {
        return $this->packageConfig($package)[0];
    }

    /**
     * @param PackageInterface|null $package
     * @return bool|null
     */
    public function isSymlink(?PackageInterface $package): ?bool
    {
        if (!$package) {
            return $this->isSymlink;
        }

        return $this->packageConfig($package)[2];
    }

    /**
     * @param PackageInterface|null $package
     * @return bool
     */
    public function isStrict(?PackageInterface $package): bool
    {
        if (!$package) {
            return $this->isStrict;
        }

        return $this->packageConfig($package)[1];
    }

    /**
     * @param array $extra
     * @return void
     */
    private function parseRoot(array $extra): void
    {
        $config = $extra[self::EXTRA_KEY_ROOT] ?? null;

        $publishDir = is_array($config) ? ($config[self::KEY_PUBLISH_DIR] ?? null) : null;
        if ($publishDir === null) {
            $content = $extra[self::KEY_CONTENT_DIR] ?? null;
            if (($content !== '') && is_string($content)) {
                $publishDir = rtrim($content, '\\/') . '/plugins';
            }
        }

        $this->assetsPath = (($publishDir !== '') && is_string($publishDir))
            ? $this->filesystem->normalizePath($publishDir) . '/' . ASSETS_PATH_NAME
            : null;

        if (is_array($config)) {
            [$isStrict, $isSymlink] = $this->extractOptions($config);
            $this->isStrict = $isStrict;
            $this->isSymlink = $isSymlink;
        }
    }

    /**
     * @param PackageInterface $package
     * @return Package-Config
     */
    private function packageConfig(PackageInterface $package): array
    {
        if ($this->packages->contains($package)) {
            return $this->packages->offsetGet($package);
        }

        $extra = $package->getExtra();
        if (!array_key_exists(self::EXTRA_KEY_PACKAGE, $extra)) {
            $config = [null, $this->isStrict, $this->isSymlink];
            $this->packages->offsetSet($package, $config);

            return $config;
        }

        $data = $extra[self::EXTRA_KEY_PACKAGE];
        ($data !== '' && is_string($data)) and $data = [$data];

        if (!is_array($data)) {
            $config = [[], $this->isStrict, $this->isSymlink];
            $this->packages->offsetSet($package, $config);

            return $config;
        }

        $paths = $this->extractPaths($data);
        $isStrict = $this->isStrict;
        $isSymlink = $this->isSymlink;

        if (is_array($data[self::KEY_OPTIONS] ?? null)) {
            [$isStrict, $isSymlink] = $this->extractOptions(
                (array)$data[self::KEY_OPTIONS],
                $isStrict,
                $isSymlink
            );
        }

        $config = [$paths, $isStrict, $isSymlink];
        $this->packages->offsetSet($package, $config);

        return $config;
    }

    /**
     * @param array $source
     * @return list<non-empty-string>
     */
    private function extractPaths(array $source): array
    {
        $isObject = is_array($source[self::KEY_PATHS] ?? null);
        if (!$isObject && is_string($source[self::KEY_PATHS] ?? null)) {
            $source[self::KEY_PATHS] = [$source[self::KEY_PATHS]];
            $isObject = true;
        }
        $paths = $isObject
            ? $source[self::KEY_PATHS]
            : ($this->arrayIsList($source) ? $source : []);

        $normalized = [];
        foreach ($paths as $path) {
            if (is_string($path)) {
                $path = $this->filesystem->normalizePath(trim($path));
                ($path !== '') and $normalized[$path] = 1;
            }
        }

        return array_keys($normalized);
    }

    /**
     * @param array $source
     * @param bool $isStrict
     * @param bool|null $isSymlink
     * @return list{bool, bool|null}
     */
    private function extractOptions(
        array $source,
        bool $isStrict = false,
        ?bool $isSymlink = null
    ): array {

        if (isset($source[self::KEY_STRICT])) {
            $isStrict = (bool)filter_var($source[self::KEY_STRICT], FILTER_VALIDATE_BOOLEAN);
        }

        if (is_scalar($source[self::KEY_SYMLINK] ?? null)) {
            $symlink = $source[self::KEY_SYMLINK];
            is_string($symlink) and $symlink = trim($symlink);
            if ($symlink !== '') {
                $isSymlink = (bool)filter_var($source[self::KEY_SYMLINK], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return [$isStrict, $isSymlink];
    }

    /**
     * @param array $array
     * @return bool
     *
     * @psalm-assert-if-true list $array
     */
    private function arrayIsList(array $array): bool
    {
        static $exists;
        if (!isset($exists)) {
            $exists = function_exists('array_is_list');
        }
        if ($exists) {
            return array_is_list($array);
        }

        return strpos((string)@json_encode($array), '[') === 0;
    }
}
