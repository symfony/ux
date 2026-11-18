<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class Avatar
{
    public string $name;
    public string $size = 'default';

    #[ExposeInTemplate('initials')]
    public function getInitials(): string
    {
        $words = explode(' ', trim($this->name));

        return implode('', array_map(static fn (string $word): string => strtoupper(substr($word, 0, 1)), $words));
    }
}
