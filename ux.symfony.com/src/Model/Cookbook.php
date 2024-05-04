<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class Cookbook
{
    public function __construct(
        public string $title,
        public string $description,
        public string $route,
        public string $image,
        public string $content,
        /**
         * @var string[]
         */
        public array $tags = [],
    ) {
    }
}
