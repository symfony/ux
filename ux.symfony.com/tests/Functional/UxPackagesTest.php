<?php

namespace App\Tests\Functional;

use App\Model\Package;
use App\Service\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class UxPackagesTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @dataProvider getSmokeTests
     */
    public function testPackagePagesAllLoad(Package $package, string $expectedText): void
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
        $repository = new PackageRepository();
        foreach ($repository->findAll() as $package) {
            if ($package->getName() === 'live-component') {
                // Live Component has a different bottom section
                yield $package->getName() => [$package, 'Read full Documentation'];
                continue;
            }

            yield $package->getName() => [$package, sprintf('Symfony UX %s Docs', $package->getHumanName())];
        }
    }
}
