<?php

namespace App\Twig;

use Highlight\Highlighter;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('code_block')]
class CodeBlockComponent
{
    public string $filename;
    public string $height = 'auto';
    public bool $showFilename = true;
    public ?string $language = null;

    public function __construct(private Highlighter $highlighter)
    {
    }

    public function highlight(string $content): string
    {
        return $this->highlighter->highlight($this->getLanguage(), $content)->value;
    }

    public function getClassString(): string
    {
        return 'terminal-code'.($this->showFilename ? '' : ' terminal-code-no-filename');
    }

    private function getLanguage(): string
    {
        if (null !== $this->language) {
            return $this->language;
        }

        $parts = explode('.', $this->filename);

        return array_pop($parts);
    }
}
