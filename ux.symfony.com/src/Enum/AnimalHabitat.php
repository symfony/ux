<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

enum AnimalHabitat: string
{
    case Land = 'land';
    case Water = 'water';
    case Air = 'air';

    /**
     * Return an icon name for this domain.
     */
    public function getIcon(): string
    {
        return match($this) {
            self::Air => 'lucide:wind',
            self::Land => 'lucide:mountain',
            self::Water => 'lucide:waves',
        };
    }
}
