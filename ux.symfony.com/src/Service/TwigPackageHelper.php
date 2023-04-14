<?php

namespace App\Service;

use App\Model\Package;

class TwigPackageHelper
{
    public function __construct(
        private PackageRepository $packageRepository,
        private PackageContext $packageContext
    ) {
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
            $this->packageRepository->find('vue'),
        ];
    }

    public function getCurrentPackage(): Package
    {
        return $this->packageContext->getCurrentPackage();
    }
}
