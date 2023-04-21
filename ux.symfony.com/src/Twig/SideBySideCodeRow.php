<?php

namespace App\Twig;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class SideBySideCodeRow
{
    public string $file1;
    public string $file2;
}
