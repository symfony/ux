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

enum AnimalzType: string
{
    case Normal = 'NORMAL';
    case Water = 'WATER';
    case Flying = 'FLYING';
    case Bug = 'BUG';
    case Fight = 'FIGHT';
    case Dark = 'DARK';
    case Poison = 'POISON';
    case Grass = 'GRASS';
    case Fairy = 'FAIRY';
    case Fossil = 'FOSSIL';

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => '#9fa19f',
            self::Water => '#2980ef',
            self::Flying => '#81b9ef',
            self::Bug => '#91a119',
            self::Fight => '#ff8100',
            self::Dark => '#4f3f3d',
            self::Poison => '#9141cb',
            self::Grass => '#3fa129',
            self::Fairy => '#ef70ef',
            self::Fossil => '#5060e1',
        };
    }
}
