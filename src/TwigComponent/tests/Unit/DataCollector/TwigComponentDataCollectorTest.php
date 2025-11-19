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

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\DataCollector\TwigComponentDataCollector;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;
use Symfony\UX\TwigComponent\MountedComponent;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentDataCollectorTest extends TestCase
{
    public function testCollectDoesNothing()
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $this->assertSame([], $dataCollector->getData());

        $dataCollector->collect(new Request(), new Response());
        $this->assertSame([], $dataCollector->getData());
    }

    public function testLateCollectWithNoCollectedData()
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

        $this->assertEquals(0.0, $dataCollector->getRenderTime());
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testLateCollectWithCollectedData(bool $collectComponents)
    {
        $logger = new TwigComponentLoggerListener();
        $twig = new Environment(new ArrayLoader());
        $dataCollector = new TwigComponentDataCollector($logger, $twig, $collectComponents);

        // Trigger some events to be logged
        $mounted = new MountedComponent('foo', new \stdClass(), new ComponentAttributes([], new EscaperRuntime()));
        $eventA = new PreRenderEvent($mounted, new ComponentMetadata(['key' => 'foo', 'template' => 'bar']), []);
        $logger->onPreRender($eventA);
        $eventB = new PostRenderEvent($mounted);
        $logger->onPostRender($eventB);

        $dataCollector->lateCollect();

        $this->assertSame(1, $dataCollector->getComponentCount());
        $this->assertIsIterable($dataCollector->getComponents());
        $this->assertNotEmpty($dataCollector->getComponents());

        $this->assertSame(1, $dataCollector->getRenderCount());
        $this->assertIsIterable($dataCollector->getRenders());
        $this->assertNotEmpty($dataCollector->getRenders());

        foreach ($dataCollector->getRenders() as $render) {
            if ($collectComponents) {
                $this->assertNotNull($render['component']);
            } else {
                $this->assertNull($render['component']);
            }
        }

        $this->assertGreaterThan(0.0, $dataCollector->getRenderTime());
    }

    public function testReset()
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $dataCollector->lateCollect();
        $this->assertNotSame([], $dataCollector->getData());

        $dataCollector->reset();
        $this->assertSame([], $dataCollector->getData());
    }

    public function testGetName()
    {
        $logger = new TwigComponentLoggerListener();
        $twig = $this->createMock(Environment::class);
        $dataCollector = new TwigComponentDataCollector($logger, $twig);

        $this->assertEquals('twig_component', $dataCollector->getName());
    }
}
