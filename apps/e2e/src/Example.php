<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

final readonly class Example
{
    public function __construct(
        public UxPackage $uxPackage,
        public string $name,
        public string $description,
        public string $url
    ) {
    }
}
