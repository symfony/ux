<?php

namespace App\Twig;

use App\Model\UxPackage;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class PackageBox
{
    public UxPackage $package;
}
