<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * Change the template of "FooBarBaz:" components during PrePrenderEvent.
 * Before:
 *      FooBar:Baz -> FooBar/Baz.html.twig
 * After:
 *      FooBar:Baz -> FooBar/Baz.foo_bar.html.twig
 *
 * @see PreRenderEvent
 */
class FooBarComponentTemplateListener
{
    #[AsEventListener]
    public function onPreRender(PreRenderEvent $event): void
    {
        $metadata = $event->getMetadata();
        if (!str_starts_with($metadata->getName(), 'FooBar:')) {
            return;
        }

        if (!str_ends_with($event->getTemplate(), '.html.twig')) {
            return;
        }

        $template = substr($event->getTemplate(), 0, -strlen('.html.twig')).'.foo_bar.html.twig';
        $event->setTemplate($template);
    }
}
