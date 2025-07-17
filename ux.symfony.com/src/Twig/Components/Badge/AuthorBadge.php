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

use App\Model\Person;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'Badge:Author',
    template: 'components/Badge.html.twig',
    exposePublicProps: false,
)]
final class AuthorBadge extends Badge
{
    public Person|string $author;

    public string $label = 'Author';

    public string $icon = 'lucide:user-round';

    public function mount(string $author): void
    {
        $this->value = $author;
    }
}
