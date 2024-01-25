<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Icon;

use App\Model\Icon\IconSet;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Icon:IconSetCard')]
class IconSetCard
{
    public IconSet $iconSet;

    public int $index = 0;

    public int $samples = 10;

    private const ICONSET_SAMPLES = [
        'lucide' => [
            'home', 'user', 'settings', 'search', 'arrow-down',
            'heart', 'star', 'sun', 'grid', 'image',
        ],
        'bi' => [
            'house', 'person', 'gear', 'search', 'arrow-down',
            'heart', 'star', 'sun', 'grid', 'image',
        ],
        'bx' => [
            'home', 'user', 'certification', 'search', 'down-arrow-alt',
            'heart', 'star', 'sun', 'grid', 'image',
        ],
        'tabler' => [
            'home', 'user', 'settings', 'search', 'arrow-down',
            'heart', 'star', 'sun', 'grid', 'photo',
        ],
        'flowbite' => [
            'home-outline', 'user-outline', 'badge-check-outline', 'search-outline', 'arrow-down-outline',
            'heart-outline', 'star-outline', 'sun-outline', 'grid-outline', 'image-outline',
        ],
        'iconoir' => [
            'home', 'user', 'settings', 'search', 'arrow-down',
            'heart', 'star', 'sun-light', 'view-grid', 'media-image',
        ],
    ];

    /**
     * ['home', 'house'],
     * ['user', 'person', 'profile'],
     * ['settings', 'cog', 'gear'],
     * ['search', 'magnifying-glass'],
     * ['arrow-down', 'arrow-bottom', 'arrow-to-bottom', 'down-two'],
     *
     * ['love', 'heart', 'heart-check'],
     * ['star', 'star-empty'],
     * ['sun', 'sun-light'],
     * ['grid', 'layout-grid', 'view-grid', 'grid-four', 'grid-on'],
     * ['image', 'photo', 'media-image'],
     *
     * ['edit', 'pencil', 'note-pencil'],
     * ['trash', 'bin', 'delete-bin', 'trash-can', 'trash-bin'],
     * ['map', 'map-trifold'],
     * ['cart', 'shopping-cart'],
     * ['check-circle', 'checkmark-circle', 'circle-check', 'checkbox-circle'],
     */

    public function getSampleIcons(): array
    {
        return self::ICONSET_SAMPLES[$this->iconSet->getIdentifier()] ?? [];
    }
}
