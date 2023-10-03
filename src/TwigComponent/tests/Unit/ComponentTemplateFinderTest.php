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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComponentTemplateFinderTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testFindTemplate(): void
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
        $environment = $this->createEnvironment($templates);
        $finder = new ComponentTemplateFinder($environment, 'components');

        $this->assertEquals('components/aa.html.twig', $finder->findAnonymousComponentTemplate('aa'));
        $this->assertEquals('components/aa/bb.html.twig', $finder->findAnonymousComponentTemplate('aa:bb'));
        $this->assertEquals('components/b.html.twig', $finder->findAnonymousComponentTemplate('b'));

        $this->assertNull($finder->findAnonymousComponentTemplate('b.html.twig'));
        $this->assertNull($finder->findAnonymousComponentTemplate('components:b'));
        $this->assertNull($finder->findAnonymousComponentTemplate('c'));
    }

    /**
     * @group legacy
     */
    public function testFindTemplateWithLegacyAutonaming(): void
    {
        $templates = [
            'components/aa.html.twig',
            'components/aa:bb.html.twig',
            'components/aa/bb.html.twig',
            'a',
            'components/b',
            'components/b.html.twig',
            'b',
            'b.html.twig',
            'components/c',
            'c',
            'c.html.twig',
            'd',
            'components/d',
        ];
        $environment = $this->createEnvironment($templates);

        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: The "Symfony\UX\TwigComponent\ComponentTemplateFinder::__construct()" method will require "string $directory" argument in 3.0. Not defining it or passing null is deprecated.');
        $finder = new ComponentTemplateFinder($environment);

        $this->assertEquals('components/aa.html.twig', $finder->findAnonymousComponentTemplate('aa'));
        $this->assertEquals('components/aa/bb.html.twig', $finder->findAnonymousComponentTemplate('aa:bb'));
        $this->assertEquals('components/b.html.twig', $finder->findAnonymousComponentTemplate('b'));
        $this->assertEquals('components/b.html.twig', $finder->findAnonymousComponentTemplate('components:b'));
        $this->assertEquals('c.html.twig', $finder->findAnonymousComponentTemplate('c'));
        $this->assertEquals('components/d', $finder->findAnonymousComponentTemplate('d'));

        $this->assertNull($finder->findAnonymousComponentTemplate('nope'));
        $this->assertNull($finder->findAnonymousComponentTemplate('nope:bar'));
    }

    /**
     * @group legacy
     */
    public function testTriggerDeprecationWhenDirectoryArgumentIsNullOrNotProvided(): void
    {
        $environment = $this->createEnvironment([]);

        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: The "Symfony\UX\TwigComponent\ComponentTemplateFinder::__construct()" method will require "string $directory" argument in 3.0. Not defining it or passing null is deprecated.');
        $finder = new ComponentTemplateFinder($environment);

        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: The "Symfony\UX\TwigComponent\ComponentTemplateFinder::__construct()" method will require "string $directory" argument in 3.0. Not defining it or passing null is deprecated.');
        $finder = new ComponentTemplateFinder($environment, null);
    }

    public function testFindTemplateWithinDirectory(): void
    {
        $templates = [
            'bar.html.twig',
            'foo/bar.html.twig',
            'bar/foo/bar.html.twig',
            'foo/foo/bar.html.twig',
        ];
        $environment = $this->createEnvironment($templates);
        $finder = new ComponentTemplateFinder($environment, 'foo');

        $this->assertEquals('foo/bar.html.twig', $finder->findAnonymousComponentTemplate('bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));
        $this->assertEquals('foo/foo/bar.html.twig', $finder->findAnonymousComponentTemplate('foo:bar'));
    }

    private function createEnvironment(array $templates): Environment
    {
        $loader = new ArrayLoader(array_combine($templates, $templates));

        return new Environment($loader);
    }
}
