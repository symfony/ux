<?php

namespace App\Service;

use App\Model\UxPackage;

class TwigPackageHelper
{
    public function __construct(
        private UxPackageRepository $packageRepository,
        private PackageContext $packageContext
    ) {
    }

    /**
     * @return array<UxPackage>
     */
    public function getTopNavPackages(): array
    {
        return [
            $this->packageRepository->find('live-component'),
            $this->packageRepository->find('autocomplete'),
            $this->packageRepository->find('react'),
            $this->packageRepository->find('vue'),
            $this->packageRepository->find('translator'),
        ];
    }

    public function getCurrentPackage(): UxPackage
    {
        return $this->packageContext->getCurrentPackage();
    }
}
