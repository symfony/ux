<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Service\EmojiCollection;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('ProductGrid')]
class ProductGrid
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    private const PER_PAGE = 10;

    #[LiveProp]
    public int $page = 1;

    public function __construct(private readonly EmojiCollection $emojis)
    {
    }

    #[LiveAction]
    public function more(): void
    {
        ++$this->page;
    }

    public function hasMore(): bool
    {
        return \count($this->emojis) > ($this->page * self::PER_PAGE);
    }

    public function getItems(): array
    {
        $emojis = $this->emojis->paginate($this->page, self::PER_PAGE);
        $colors = $this->getColors();

        $items = [];
        foreach ($emojis as $i => $emoji) {
            $items[] = [
                'id' => $id = ($this->page - 1) * self::PER_PAGE + $i,
                'emoji' => $emoji,
                'color' => $colors[$id % count($colors)],
            ];
        }

        return $items;
    }

    public function getColors(): array
    {
        return [
            '#fbf8cc', '#fde4cf', '#ffcfd2',
            '#f1c0e8', '#cfbaf0', '#a3c4f3',
            '#90dbf4', '#8eecf5', '#98f5e1',
            '#b9fbc0', '#b9fbc0', '#ffc9c9',
            '#d7ffc9', '#c9fffb',
        ];
    }
}
