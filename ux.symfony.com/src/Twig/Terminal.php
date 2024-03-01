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

use App\Util\SourceCleaner;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Terminal
{
    public int $bottomPadding = 100;
    public string $height = 'auto';
    public bool $processContents = true;

    public function process(string $content): string
    {
        if (!$this->processContents) {
            return $content;
        }

        return SourceCleaner::processTerminalLines($content);
    }
}
