<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
final class AnonymousComponent
{
    private array $props;

    public function mount($props = []): void
    {
        $this->props = $props;
    }

    #[ExposeInTemplate(destruct: true)]
    public function getProps(): array
    {
        return $this->props;
    }
}
