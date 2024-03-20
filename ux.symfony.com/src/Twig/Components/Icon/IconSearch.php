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

use App\Model\Icon\Icon;
use App\Model\Icon\IconSet;
use App\Service\Icon\Iconify;
use App\Service\Icon\IconSetRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent('Icon:IconSearch')]
class IconSearch
{
    use DefaultActionTrait;

    private const PER_PAGE = 256;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    #[LiveProp(writable: true, url: true)]
    public ?string $set = null;

    private array $icons;

    public function __construct(
        private readonly Iconify $iconify,
        private readonly IconSetRepository $iconSetRepository,
    ) {
    }

    #[PostMount]
    public function postMount(): void
    {
    }

    public function getIconSetOptionGroups(): array
    {
        $groups = [];
        $groups['Favorites'] = [];
        foreach ($this->iconSetRepository->findAll() as $iconSet) {
            $category = $iconSet->getCategory() ?? IconSet::CATEGORY_UNCATEGORIZED;
            if ($iconSet->isFavorite()) {
                $category = 'Favorites';
            }

            $groups[$category] ??= [];
            $groups[$category][$iconSet->getIdentifier()] = $iconSet->getName();
        }
        foreach ($groups as $category => $iconSets) {
            asort($iconSets);
            $groups[$category] = $iconSets;
        }
        return $groups;
    }

    public function getHash(): string
    {
        return substr(md5(serialize([$this->query, $this->set])), 0, 8);
    }

    public function icons(): array
    {
        return $this->icons ??= $this->searchIcons();
    }

    private function searchIcons(): array
    {
        if (!$this->query) {
            if (!$this->set) {
                return [];
            }

            $icons = array_slice($this->iconify->collectionIcons($this->set), 0, self::PER_PAGE);

            return array_map(fn ($name) => Icon::create($this->set, $name), $icons);
        }

        $icons = $this->iconify->search($this->query, $this->set, self::PER_PAGE)['icons'];

        return array_map(fn ($icon) => Icon::fromIdentifier($icon), $icons);
    }
}
