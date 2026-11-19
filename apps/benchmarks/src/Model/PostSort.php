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

enum PostSort: string
{
    case Newest = 'newest';
    case Oldest = 'oldest';
    case MostViewed = 'most-viewed';
    case MostCommented = 'most-commented';
    case Title = 'title';

    public function label(): string
    {
        return match ($this) {
            self::Newest => 'Newest first',
            self::Oldest => 'Oldest first',
            self::MostViewed => 'Most viewed',
            self::MostCommented => 'Most commented',
            self::Title => 'Title (A-Z)',
        };
    }
}
