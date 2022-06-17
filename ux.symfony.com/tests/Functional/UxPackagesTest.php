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
    public function testPackagePagesAllLoad(Package $package): void
    {
        $this->browser()
            ->visit('/'.$package->getName())
            ->assertSuccessful()
            ->assertSeeIn('title', $package->getHumanName())
            ->assertSee(sprintf('Symfony UX %s Docs', $package->getHumanName()))
        ;
    }

    public function getSmokeTests(): \Generator
    {
        $repository = new PackageRepository();
        foreach ($repository->findAll() as $package) {
            yield $package->getName() => [$package];
        }
    }
}
