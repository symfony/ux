<?php

namespace App\Twig;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('docs_link')]
class DocsLinkComponent
{
    public string $url;
    public string $title;
    public string $text;

    #[ExposeInTemplate]
    public function isExternal(): bool
    {
        return !str_starts_with($this->url, 'https://symfony.com');
    }
}
