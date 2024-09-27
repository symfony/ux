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
    name: 'Badge:Github',
    template: 'components/Badge.html.twig',
    exposePublicProps: false,
)]
final class GithubBadge extends Badge
{
    public string $username;

    public string $label = 'Github';

    public string $icon = 'github';

    public function mount(string $username): void
    {
        $this->value = $username;
        $this->url = 'https://github.com/'.$username;
    }
}
