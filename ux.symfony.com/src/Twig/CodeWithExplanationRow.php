<?php

namespace App\Twig;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class CodeWithExplanationRow
{
    public string $filename;

    public bool $reversed = false;
}
