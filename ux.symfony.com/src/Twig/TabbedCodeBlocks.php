<?php

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
