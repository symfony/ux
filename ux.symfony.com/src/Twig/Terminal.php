<?php

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
