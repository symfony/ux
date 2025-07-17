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

#[AsTwigComponent('Code:CodeWithExplanationRow')]
class CodeWithExplanationRow
{
    public string $filename;

    public ?int $lineStart = null;

    public ?int $lineEnd = null;

    public bool $reversed = false;

    public bool $sticky = false;

    public ?string $targetTwigBlock = null;

    public bool $stripExcessHtml = false;

    public bool $showFilename = true;

    public bool $copyButton = true;

    public int $codeCols = 6;
}
