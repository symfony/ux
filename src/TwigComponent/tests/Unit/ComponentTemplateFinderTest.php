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
            'foo/qux/index.html.twig',
            'foo/foo/baz/index.html.twig',
        ];
        $loader = $this->createLoader($templates);
        $finder = new ComponentTemplateFinder($loader, 'foo');

        $this->assertEquals('foo/bar.html.twig', $finder->findAnonymousComponentTemplate('bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));

        $this->assertEquals('foo/qux/index.html.twig', $finder->findAnonymousComponentTemplate('qux'));
        $this->assertEquals('foo/foo/baz/index.html.twig', $finder->findAnonymousComponentTemplate('foo:baz'));
    }

    public function testFindTemplateFromExternalBundle()
    {
        $templates = [
            '@Acme/components/Button.html.twig',
            '@Acme/components/Alert.html.twig',
            '@Acme/components/Menu/index.html.twig',
            '@Acme/components/Button/Primary.html.twig',
            '@Acme/components/Button/Secondary/index.html.twig',
            '@Acme/components/Card/Header/index.html.twig',
            '@Acme/components/Index.html.twig',
            '@Acme/components/Index/index.html.twig',
            '@Acme/components/index.html.twig',
            '@Acme/components/index/index.html.twig',
            '@Acme/components/Foo/Index.html.twig',
            '@Acme/components/Foo/Index/index.html.twig',
            '@Acme/components/Foo/index.html.twig',
            '@Acme/components/Foo/index/index.html.twig',
            '@Acme/components/Index/Index.html.twig',
            '@Acme/components/Index/Index/index.html.twig',
            '@Acme/components/Index/index.html.twig',
        ];
        $loader = $this->createLoader($templates);
        $finder = new ComponentTemplateFinder($loader, 'components');

        // Regular .html.twig files from external bundles
        $this->assertSame('@Acme/components/Button.html.twig', $finder->findAnonymousComponentTemplate('Acme:Button'));
        $this->assertSame('@Acme/components/Alert.html.twig', $finder->findAnonymousComponentTemplate('Acme:Alert'));

        // index.html.twig files from external bundles
        $this->assertSame('@Acme/components/Menu/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Menu'));

        // Nested components with regular .html.twig
        $this->assertSame('@Acme/components/Button/Primary.html.twig', $finder->findAnonymousComponentTemplate('Acme:Button:Primary'));

        // Nested components with index.html.twig
        $this->assertSame('@Acme/components/Button/Secondary/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Button:Secondary'));
        $this->assertSame('@Acme/components/Card/Header/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Card:Header'));

        // Edge cases: Component named "Index" (capitalized)
        // When both Index.html.twig and Index/index.html.twig exist, Index.html.twig takes precedence
        $this->assertSame('@Acme/components/Index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Index'));

        // Edge cases: Component named "index" (lowercase)
        $this->assertSame('@Acme/components/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:index'));

        // Edge cases: Nested component Acme:Foo:Index
        $this->assertSame('@Acme/components/Foo/Index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Foo:Index'));

        // Edge cases: Acme:Foo:index
        // The current implementation allows both Acme:Foo and Acme:Foo:index to match the same template.
        $this->assertSame('@Acme/components/Foo/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Foo'));
        $this->assertSame('@Acme/components/Foo/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Foo:index'));

        // Edge cases: Acme:Index:index (directory "Index" with component "index")
        // Note: Acme:Index matches Index.html.twig (precedence), not Index/index.html.twig
        // See separate test case below.
        $this->assertSame('@Acme/components/Index/index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Index:index'));

        // Edge cases: Acme:Index:Index (directory "Index" with component "Index")
        $this->assertSame('@Acme/components/Index/Index.html.twig', $finder->findAnonymousComponentTemplate('Acme:Index:Index'));

        // Non-existent components
        $this->assertNull($finder->findAnonymousComponentTemplate('Acme:NonExistent'));
        $this->assertNull($finder->findAnonymousComponentTemplate('Acme:Button:NonExistent'));

        // Test case: Acme:Index should match Index/index.html.twig when Index.html.twig doesn't exist
        $templatesWithoutIndexHtml = [
            '@Acme/components/Index/index.html.twig',
        ];
        $loaderWithoutIndexHtml = $this->createLoader($templatesWithoutIndexHtml);
        $finderWithoutIndexHtml = new ComponentTemplateFinder($loaderWithoutIndexHtml, 'components');
        $this->assertSame('@Acme/components/Index/index.html.twig', $finderWithoutIndexHtml->findAnonymousComponentTemplate('Acme:Index'));
    }

    public function testFindTemplateFromExternalBundleWithPrecedence()
    {
        $templates = [
            '@Acme/components/Button.html.twig',
            '@Acme/components/Button/index.html.twig',
            '@Acme/components/Menu.html.twig',
            '@Acme/components/Menu/index.html.twig',
        ];
        $loader = $this->createLoader($templates);
        $finder = new ComponentTemplateFinder($loader, 'components');

        // Regular .html.twig should take precedence over index.html.twig
        $this->assertSame('@Acme/components/Button.html.twig', $finder->findAnonymousComponentTemplate('Acme:Button'));
        $this->assertSame('@Acme/components/Menu.html.twig', $finder->findAnonymousComponentTemplate('Acme:Menu'));
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
