<?php

namespace App\Service;

use App\Model\Package;

class TwigPackageHelper
{
    public function __construct(private PackageRepository $packageRepository)
    {
    }

    /**
     * @return array<Package>
     */
    public function getTopNavPackages(): array
    {
        return [
            $this->packageRepository->find('live-component'),
            $this->packageRepository->find('autocomplete'),
            $this->packageRepository->find('react'),
        ];
    }
}
