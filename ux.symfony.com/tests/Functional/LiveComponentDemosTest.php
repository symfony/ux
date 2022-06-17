<?php

namespace App\Tests\Functional;

use App\Model\LiveDemo;
use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class LiveComponentDemosTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @dataProvider getSmokeTests
     */
    public function testDemoPagesAllLoad(LiveDemo $liveDemo): void
    {
        $this->browser()
            ->visit('/live-component/demos/'.$liveDemo->getIdentifier())
            ->assertSuccessful()
            ->assertSeeIn('h1', $liveDemo->getName())
        ;
    }

    public function getSmokeTests(): \Generator
    {
        $demoRepository = new LiveDemoRepository();
        foreach ($demoRepository->findAll() as $demo) {
            yield $demo->getIdentifier() => [$demo];
        }
    }
}
