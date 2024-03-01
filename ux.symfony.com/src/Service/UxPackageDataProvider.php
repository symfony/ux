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
