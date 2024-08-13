<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Extension;

use App\Service\UxPackageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Simon André
 */
final class PackageExtension extends AbstractExtension
{
    public function __construct(
        private readonly UxPackageRepository $packageRepository,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ux_package', $this->packageRepository->find(...)),
        ];
    }
}
