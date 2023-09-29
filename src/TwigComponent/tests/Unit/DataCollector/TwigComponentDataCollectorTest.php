<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\TwigComponent\DataCollector\TwigComponentDataCollector;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;
use Twig\Environment;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentDataCollectorTest extends TestCase
{
    public function testCollectDoesNothing(): void
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $this->assertSame([], $dataCollector->getData());

        $dataCollector->collect(new Request(), new Response());
        $this->assertSame([], $dataCollector->getData());
    }

    public function testLateCollect(): void
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $dataCollector->lateCollect();

        $this->assertSame(0, $dataCollector->getComponentCount());
        $this->assertIsIterable($dataCollector->getComponents());
        $this->assertEmpty($dataCollector->getComponents());

        $this->assertSame(0, $dataCollector->getRenderCount());
        $this->assertIsIterable($dataCollector->getRenders());
        $this->assertEmpty($dataCollector->getRenders());

        $this->assertSame(0, $dataCollector->getRenderTime());
    }

    public function testReset(): void
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $dataCollector->lateCollect();
        $this->assertNotSame([], $dataCollector->getData());

        $dataCollector->reset();
        $this->assertSame([], $dataCollector->getData());
    }

    public function testGetName(): void
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $this->assertEquals('twig_component', $dataCollector->getName());
    }
}
