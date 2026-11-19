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

enum PostCategory: string
{
    case Tutorial = 'tutorial';
    case News = 'news';
    case Release = 'release';
    case Community = 'community';
    case Performance = 'performance';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Tutorial => 'Tutorial',
            self::News => 'News',
            self::Release => 'Release',
            self::Community => 'Community',
            self::Performance => 'Performance',
            self::Security => 'Security',
        };
    }
}
