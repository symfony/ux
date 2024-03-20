<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Model\UxPackage;

class TwigPackageHelper
{
    public function __construct(
        private UxPackageRepository $packageRepository,
        private PackageContext $packageContext,
    ) {
    }

    /**
     * @return array<UxPackage>
     */
    public function getTopNavPackages(): array
    {
        return [
            $this->packageRepository->find('live-component'),
            $this->packageRepository->find('twig-component'),
            $this->packageRepository->find('autocomplete'),
            $this->packageRepository->find('translator'),
            $this->packageRepository->find('react'),
            $this->packageRepository->find('vue'),
        ];
    }

    public function getCurrentPackage(): UxPackage
    {
        return $this->packageContext->getCurrentPackage();
    }
}
