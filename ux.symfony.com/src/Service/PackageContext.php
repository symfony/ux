<?php

namespace App\Service;

use App\Model\UxPackage;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to track the current package being viewed.
 */
class PackageContext
{
    private ?string $packageName = null;

    public function __construct(
        private UxPackageRepository $packageRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function getCurrentPackage(): UxPackage
    {
        if (null !== $this->packageName) {
            return $this->packageRepository->find($this->packageName);
        }

        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');

        return $this->packageRepository->findByRoute($route);
    }

    public function setCurrentPackage(string $packageName): void
    {
        $this->packageName = $packageName;
    }
}
