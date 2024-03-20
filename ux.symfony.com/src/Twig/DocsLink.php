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

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class DocsLink
{
    public string $url;
    public string $title;
    public string $text;

    public ?string $icon = null;

    #[ExposeInTemplate]
    public function isExternal(): bool
    {
        return !str_starts_with($this->url, 'https://symfony.com');
    }
}
