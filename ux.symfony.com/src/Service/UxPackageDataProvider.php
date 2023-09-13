<?php

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UxPackageDataProvider
{
    public function __construct(
        private UxPackageRepository $packageRepository,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function getPackages(): array
    {
        $packages = $this->packageRepository->findAll();

        return $this->normalizer->normalize($packages);
    }
}
