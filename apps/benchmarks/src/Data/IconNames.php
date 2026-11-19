<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Data;

final class IconNames
{
    public const REPEAT = 20;

    private const NAMES = [
        'activity',
        'alert-circle',
        'archive',
        'arrow-down',
        'arrow-right',
        'arrow-up',
        'bar-chart',
        'bell',
        'bookmark',
        'calendar',
        'camera',
        'check',
        'chevron-down',
        'chevron-left',
        'chevron-right',
        'chevron-up',
        'circle',
        'clock',
        'cloud',
        'code',
        'columns-3',
        'copy',
        'download',
        'edit',
        'external-link',
        'eye',
        'file',
        'filter',
        'folder',
        'globe',
        'heart',
        'home',
        'image',
        'info',
        'link',
        'lock',
        'mail',
        'map-pin',
        'menu',
        'message-square',
        'more-horizontal',
        'plus',
        'search',
        'settings',
        'share',
        'star',
        'tag',
        'trash-2',
        'upload',
        'user',
    ];

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return self::NAMES;
    }
}
