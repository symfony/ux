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

enum PostStatus: string
{
    case Published = 'published';
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Published',
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Archived => 'Archived',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Published => 'default',
            self::Draft => 'secondary',
            self::Scheduled => 'outline',
            self::Archived => 'destructive',
        };
    }
}
