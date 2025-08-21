<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComponentTemplateFinderTest extends TestCase
{
    public function testFindTemplate()
    {
        $templates = [
            'components/aa.html.twig',
            'components/aa:bb.html.twig',
            'components/bb.html.twig',
            'components/aa/bb.html.twig',
            'b',
            'components/b',
            'components/b.html.twig',
            'components/c',
        ];
        $loader = $this->createLoader($templates);
        $finder = new ComponentTemplateFinder($loader, 'components');

        $this->assertEquals('components/aa.html.twig', $finder->findAnonymousComponentTemplate('aa'));
        $this->assertEquals('components/aa/bb.html.twig', $finder->findAnonymousComponentTemplate('aa:bb'));
        $this->assertEquals('components/b.html.twig', $finder->findAnonymousComponentTemplate('b'));

        $this->assertNull($finder->findAnonymousComponentTemplate('b.html.twig'));
        $this->assertNull($finder->findAnonymousComponentTemplate('components:b'));
        $this->assertNull($finder->findAnonymousComponentTemplate('c'));
    }

    public function testFindTemplateWithinDirectory()
    {
        $templates = [
            'bar.html.twig',
            'foo/bar.html.twig',
            'bar/foo/bar.html.twig',
            'foo/foo/bar.html.twig',
        ];
        $loader = $this->createLoader($templates);
        $finder = new ComponentTemplateFinder($loader, 'foo');

        $this->assertEquals('foo/bar.html.twig', $finder->findAnonymousComponentTemplate('bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));
    }

    private function createLoader(array $templates): LoaderInterface
    {
        return new ArrayLoader(array_combine($templates, $templates));
    }

    private function createEnvironment(array $templates): Environment
    {
        return new Environment($this->createLoader($templates));
    }
}
