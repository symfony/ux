<?php

namespace App\Service;

use App\Model\Package;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to track the current package being viewed.
 */
class PackageContext
{
    private ?string $packageName = null;

    public function __construct(
        private PackageRepository $packageRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function getCurrentPackage(): Package
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
