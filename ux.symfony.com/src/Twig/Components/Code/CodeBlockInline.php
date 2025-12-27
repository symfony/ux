<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Code;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CodeBlockInline', template: 'components/Code/CodeBlockInline.html.twig')]
class CodeBlockInline
{
    public string $code;
    public string $language;
    public ?string $filename = null;
}
