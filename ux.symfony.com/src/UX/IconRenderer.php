<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\UX;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\UX\Icons\IconRendererInterface;

#[AsDecorator(decorates: '.ux_icons.icon_renderer')]
class IconRenderer implements IconRendererInterface
{
    public function __construct(
        #[AutowireDecorated]
        private IconRendererInterface $inner,
    ) {
    }

    public function renderIcon(string $name, array $attributes = []): string
    {
        return $this->inner->renderIcon($name, [
            ...$attributes,
            'class' => \sprintf('%s %s', 'Icon', $attributes['class'] ?? ''),
        ]);
    }
}
