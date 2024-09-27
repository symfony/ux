<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Badge;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'Badge:Package',
    template: 'components/Badge.html.twig',
    exposePublicProps: false,
)]
final class PackageBadge extends Badge
{
    public string $label = 'UX';

    public string $icon = 'lucide:box';

    public function mount(string $package): void
    {
        $this->value = $package;
    }
}
