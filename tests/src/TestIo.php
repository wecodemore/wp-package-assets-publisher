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
use Composer\IO\NullIO;

class TestIo extends NullIO
{
    /** @var list<string> */
    public array $outputs = [];
    /** @var list<string> */
    public array $errors = [];
    public int $verbosity;

    /**
     * @param int $verbosity
     */
    public function __construct(int $verbosity = IOInterface::NORMAL)
    {
        $this->verbosity = $verbosity;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return bool
     */
    public function hasOutput(): bool
    {
        return $this->outputs !== [];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->hasOutput() && !$this->hasErrors();
    }

    /**
     * @return void
     */
    public function resetAllTestWrites(): void
    {
        $this->outputs = [];
        $this->errors = [];
    }

    /**
     * @param non-empty-string $regex
     * @return bool
     */
    public function hasOutputThatMatches(string $regex): bool
    {
        return $this->hasMessageThatMatches($regex, $this->outputs);
    }

    /**
     * @param non-empty-string $regex
     * @return bool
     */
    public function hasErrorThatMatches(string $regex): bool
    {
        return $this->hasMessageThatMatches($regex, $this->errors);
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @return void
     */
    public function write($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        $this->executeTestWrite($messages, $newline, $verbosity, false);
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @return void
     */
    public function writeError($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        $this->executeTestWrite($messages, $newline, $verbosity, true);
    }

    /**
     * @param string $regex
     * @param list<string> $messages
     * @return bool
     */
    private function hasMessageThatMatches(string $regex, array $messages): bool
    {
        foreach ($messages as $message) {
            if (preg_match($regex, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @param bool $isError
     * @return void
     */
    private function executeTestWrite($messages, bool $newline, int $verbosity, bool $isError): void
    {
        if ($verbosity > $this->verbosity) {
            return;
        }
        is_string($messages) and $messages = [$messages];
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $isError
                    ? $this->errors[] = $newline ? "{$message}\n" : $message
                    : $this->outputs[] = $newline ? "{$message}\n" : $message;
            }
        }
    }
}
