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
    name: 'Badge:Date',
    template: 'components/Badge.html.twig',
    exposePublicProps: false,
)]
final class DateBadge extends Badge
{
    public string $label = 'Date';

    public string $icon = 'lucide:calendar';

    public string $format = 'Y-m-d';

    public function mount(string|\DateTimeImmutable $date): void
    {
        if (\is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }
        $this->value = $date->format($this->format);
    }
}
