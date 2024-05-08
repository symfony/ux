<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Package;

use App\Model\UxPackage;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('PackageBox')]
class PackageBox
{
    public UxPackage $package;

    public string $titleTag = 'h3';
}
