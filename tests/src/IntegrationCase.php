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

use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class IntegrationCase extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        $filesystem->emptyDirectory($this->outputPath());
        parent::setUp();
    }

    /**
     * @param string $name
     * @return PackageInterface
     */
    protected function loadPackage(string $name): PackageInterface
    {
        $path = $this->packagesPath("{$name}/composer.json");
        $json = new JsonFile($path, null, $this->factoryIo());
        $loader = new ArrayLoader();

        $data = $json->read();
        $data['version'] = '1';

        return $loader->load($data);
    }
}
