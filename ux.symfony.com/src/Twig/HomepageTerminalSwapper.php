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

use App\Model\UxPackage;
use App\Service\UxPackageRepository;
use App\Util\SourceCleaner;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class HomepageTerminalSwapper
{
    public function __construct(private UxPackageRepository $packageRepository)
    {
    }

    #[ExposeInTemplate]
    public function getTypedStrings(): array
    {
        $strings = [];
        $packages = array_filter($this->packageRepository->findAll(), fn (UxPackage $p): bool => null !== $p->getCreateString());

        shuffle($packages);

        foreach ($packages as $package) {
            $str = SourceCleaner::processTerminalLines('// '.$package->getCreateString());
            $str .= '^500<br>';
            $str .= SourceCleaner::processTerminalLines('composer require '.$package->getComposerName());

            $strings[] = $str;
        }

        return $strings;
    }
}
