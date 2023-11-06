<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Model\UxPackage;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class UxPackagesTest extends KernelTestCase
{
    use HasBrowser;

    public function testAllPackagesPage(): void
    {
        $this->browser()
            ->visit('/packages')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Packages')
            ->assertSee('All Packages')
        ;
    }

    /**
     * @dataProvider getSmokeTests
     */
    public function testPackagePagesAllLoad(UxPackage $package, string $expectedText): void
    {
        $this->browser()
            ->visit('/'.$package->getName())
            ->assertSuccessful()
            ->assertSeeIn('title', $package->getHumanName())
            ->assertSee($expectedText)
        ;
    }

    public function getSmokeTests(): \Generator
    {
        $repository = new UxPackageRepository();
        foreach ($repository->findAll() as $package) {
            if ('live-component' === $package->getName()) {
                // Live Component has a different bottom section
                yield $package->getName() => [$package, 'Read full Documentation'];
                continue;
            }

            yield $package->getName() => [$package, sprintf('Symfony UX %s Docs', $package->getHumanName())];
        }
    }
}
