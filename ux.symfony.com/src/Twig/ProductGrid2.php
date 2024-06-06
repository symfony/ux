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
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('ProductGrid2')]
class ProductGrid2
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    private const PER_PAGE = 9;

    #[LiveProp]
    public int $page = 1;

    public function __construct(private readonly EmojiCollection $emojis)
    {
    }

    public function getItems(): array
    {
        $items = [];
        $emojis = $this->emojis->paginate($this->page, self::PER_PAGE);
        foreach ($emojis as $i => $emoji) {
            $items[] = [
                'id' => ($this->page - 1) * self::PER_PAGE + $i,
                'emoji' => $emoji,
            ];
        }

        return $items;
    }

    #[ExposeInTemplate('per_page')]
    public function getPerPage(): int
    {
        return self::PER_PAGE;
    }

    public function hasMore(): bool
    {
        return \count($this->emojis) > ($this->page * self::PER_PAGE);
    }

    #[LiveAction]
    public function more(): void
    {
        ++$this->page;
    }
}
