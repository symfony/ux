<?php

namespace App\Twig;

use App\Service\PackageRepository;
use App\Util\SourceCleaner;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('homepage_terminal_swapper')]
class HomepageTerminalSwapperComponent
{
    public function __construct(private PackageRepository $packageRepository)
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
