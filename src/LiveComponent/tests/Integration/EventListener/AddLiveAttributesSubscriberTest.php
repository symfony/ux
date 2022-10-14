<?php

namespace Symfony\UX\LiveComponent\Tests\Integration\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddLiveAttributesSubscriberTest extends KernelTestCase
{
    public function testCannotUseEmbeddedComponentAsLive(): void
    {
        if (!method_exists(PreRenderEvent::class, 'isEmbedded')) {
            $this->markTestSkipped('Embedded components not available.');
        }

        $twig = self::getContainer()->get(Environment::class);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Embedded components cannot be live.');

        $twig->render('render_embedded.html.twig');
    }
}
