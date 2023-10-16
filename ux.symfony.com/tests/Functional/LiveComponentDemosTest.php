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

use App\Entity\Food;
use App\Model\LiveDemo;
use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\create;

class LiveComponentDemosTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    /**
     * @before
     */
    public function setupEntities(): void
    {
        create(Food::class, ['name' => 'Pizza', 'votes' => 10]);
    }

    /**
     * @dataProvider getSmokeTests
     */
    public function testDemoPagesAllLoad(LiveDemo $liveDemo): void
    {
        $router = self::bootKernel()->getContainer()->get('router');
        $url = $router->generate($liveDemo->getRoute());
        $this->browser()
            ->visit($url)
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
