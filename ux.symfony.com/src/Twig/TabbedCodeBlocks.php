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

use App\Util\FilenameHelper;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class TabbedCodeBlocks
{
    public array $files = [];

    public function getItemId(string $filename): string
    {
        return FilenameHelper::getElementId($filename);
    }
}
