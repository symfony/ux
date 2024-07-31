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

final readonly class Cookbook
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $title,
        public string $slug,
        public string $image,
        public string $description,
        public string $content,
        public array $tags,
    ) {
    }
}
