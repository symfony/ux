<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

class FrameLayoutTest extends TestCase
{
    private Environment $twig;
    private ChainLoader $loader;

    protected function setUp(): void
    {
        $filesystemLoader = new FilesystemLoader(__DIR__.'/../../templates');
        $filesystemLoader->addPath(__DIR__.'/../../templates', 'Turbo');

        $this->loader = new ChainLoader([new ArrayLoader(), $filesystemLoader]);
        $this->twig = new Environment($this->loader);
    }

    public function testRendersMinimalHtmlStructure()
    {
        $result = $this->twig->render('@Turbo/layouts/frame.html.twig');

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('<head>', $result);
        $this->assertStringContainsString('<body>', $result);
    }

    public function testHeadBlockIsOverridable()
    {
        $this->loader->addLoader(new ArrayLoader([
            'child.html.twig' => '{% extends "@Turbo/layouts/frame.html.twig" %}{% block head %}<meta name="test" content="present">{% endblock %}',
        ]));

        $result = $this->twig->render('child.html.twig');

        $this->assertStringContainsString('<meta name="test" content="present">', $result);
        $this->assertStringNotContainsString('<nav', $result);
    }

    public function testBodyBlockIsOverridable()
    {
        $this->loader->addLoader(new ArrayLoader([
            'child.html.twig' => '{% extends "@Turbo/layouts/frame.html.twig" %}{% block body %}<turbo-frame id="test">content</turbo-frame>{% endblock %}',
        ]));

        $result = $this->twig->render('child.html.twig');

        $this->assertStringContainsString('<turbo-frame id="test">content</turbo-frame>', $result);
    }

    public function testHeadAndBodyBlocksAreIndependent()
    {
        $this->loader->addLoader(new ArrayLoader([
            'child.html.twig' => '{% extends "@Turbo/layouts/frame.html.twig" %}{% block head %}<meta name="test" content="present">{% endblock %}{% block body %}<turbo-frame id="test">content</turbo-frame>{% endblock %}',
        ]));

        $result = $this->twig->render('child.html.twig');

        $this->assertStringContainsString('<meta name="test" content="present">', $result);
        $this->assertStringContainsString('<turbo-frame id="test">content</turbo-frame>', $result);
    }
}
