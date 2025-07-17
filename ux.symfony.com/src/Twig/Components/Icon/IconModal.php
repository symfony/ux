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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent('Icon:IconModal')]
class IconModal
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $icon = null;

    private ?Icon $iconMeta = null;

    private ?IconSet $iconSet = null;

    private ?string $svg = null;

    public function __construct(
        private readonly IconSetRepository $iconSetRepository,
        private readonly Iconify $iconify,
    ) {
    }

    #[PostHydrate]
    #[PostMount]
    public function postMountHydrate(): void
    {
        if (null === $this->icon) {
            return;
        }

        $this->iconMeta = $icon = Icon::fromIdentifier($this->icon);
        $this->iconSet = $this->iconSetRepository->get($icon->getPrefix());
    }

    #[LiveAction]
    public function loadSvg(): void
    {
        if (null === $icon = $this->iconMeta) {
            return;
        }

        $this->svg ??= $this->iconify->svg($icon->getPrefix(), $icon->getName());
    }

    public function getIconSet(): ?IconSet
    {
        return $this->iconSet;
    }

    public function getIconMeta(): ?Icon
    {
        return $this->iconMeta;
    }

    public function getSvg(): ?string
    {
        return $this->svg;
    }
}
