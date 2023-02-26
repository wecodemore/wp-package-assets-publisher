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

namespace WeCodeMore\WpPackageAssetsPublisher {

    const ASSETS_PATH_NAME = '.published-package-assets';
}

namespace WeCodeMore {

    use const WeCodeMore\WpPackageAssetsPublisher\ASSETS_PATH_NAME;

    /**
     * Return published assets base dir, that is plugins DIR + `ASSETS_PATH_NAME`.
     *
     * If the base directory was customized using `extra.package-assets-publisher.publish-dir` in
     * the root composer.json, and it does not match `WP_CONTENT_DIR . '/plugins'`, the same path
     * must be set in using either (in order of priority):
     * - `WeCodeMore\PUBLISHED_PACKAGES_DIR` constant
     * - `PUBLISHED_PACKAGES_DIR` env variable
     * This customization must happen before calling the function for the first time.
     *
     * @return string
     */
    function packageAssetsBasePath(): string
    {
        $defined = defined(__NAMESPACE__ . '\\PUBLISHED_PACKAGES_DIR');
        if ($defined && !is_string(PUBLISHED_PACKAGES_DIR)) {
            return '';
        }

        if (!$defined) {
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput
            $dir = $_SERVER['PUBLISHED_PACKAGES_DIR'] ?? null;
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput
            $dir = (is_string($dir) && ($dir !== '')) ? stripslashes($dir) : null;
            $dir = $dir ?? $_ENV['PUBLISHED_PACKAGES_DIR'] ?? getenv('PUBLISHED_PACKAGES_DIR');
            (($dir !== '') && is_string($dir) && is_dir($dir)) or $dir = null;
            $dir = $dir ?? rtrim(WP_CONTENT_DIR, '\\/') . '/plugins';
            define(__NAMESPACE__ . '\\PUBLISHED_PACKAGES_DIR', $dir);
        }

        /** @psalm-suppress MixedArgument */
        return rtrim(PUBLISHED_PACKAGES_DIR, '\\/') . '/' . ASSETS_PATH_NAME;
    }

    /**
     * Helper function to get packages' published assets URLs.
     *
     * @param string $package The full Composer package's name (vendor/name).
     * @param string $path The relative path of the asset from package's root dir.
     * @return string
     */
    function packageAssetUrl(string $package, string $path = ''): string
    {
        /** @var mixed $url */
        $url = plugins_url("/{$package}/{$path}", packageAssetsBasePath() . '/index.php');

        return is_string($url) ? $url : '';
    }
}
