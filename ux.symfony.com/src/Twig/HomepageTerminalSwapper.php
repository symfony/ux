<?php

namespace App\Twig;

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
        $packages = $this->packageRepository->findAll();
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
