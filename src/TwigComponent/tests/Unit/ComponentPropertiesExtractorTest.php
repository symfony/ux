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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentPropertiesExtractor;
use Twig\Environment;

class ComponentPropertiesExtractorTest extends KernelTestCase
{
    public function testPropsAreFoundInTwigComponent(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $twig = self::getContainer()->get(Environment::class);
        $metadata = $factory->metadataFor('DivComponent5');

        $extractor = new ComponentPropertiesExtractor($twig);
        $attributes = $extractor->getComponentProperties($metadata);

        $this->assertEquals([
            'divComponentName' => [
                'display' => 'string $divComponentName = "foo"',
                'name' => 'divComponentName',
                'type' => 'string',
                'default' => 'foo',
            ],
        ], $attributes);
    }

    public function testPropsAreFoundInTwigComponentWithoutProps(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $twig = self::getContainer()->get(Environment::class);
        $metadata = $factory->metadataFor('DivComponent6');

        $extractor = new ComponentPropertiesExtractor($twig);
        $attributes = $extractor->getComponentProperties($metadata);

        $this->assertEmpty($attributes);
    }

    public function testPropsAreFoundInTwigAnonymousComponent(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $twig = self::getContainer()->get(Environment::class);
        $metadata = $factory->metadataFor('Button');

        $extractor = new ComponentPropertiesExtractor($twig);
        $attributes = $extractor->getComponentProperties($metadata);

        $expected = [
            'label' => [
                'display' => 'label',
                'name' => 'label',
                'type' => 'mixed',
                'default' => null,
            ],
            'primary' => [
                'display' => 'primary = true',
                'name' => 'primary',
                'type' => 'mixed',
                'default' => 'true',
            ],
        ];
        $this->assertEquals($expected, $attributes);
    }

    public function testPropsAreFoundInTwigAnonymousComponentWithJusteAttributes(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $twig = self::getContainer()->get(Environment::class);
        $metadata = $factory->metadataFor('JustAttributes');

        $extractor = new ComponentPropertiesExtractor($twig);
        $attributes = $extractor->getComponentProperties($metadata);

        $this->assertEmpty($attributes);
    }

    public function testPropsAreFoundInTwigAnonymousComponentWithEmptyProps(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $twig = self::getContainer()->get(Environment::class);
        $metadata = $factory->metadataFor('EmptyProps');

        $extractor = new ComponentPropertiesExtractor($twig);
        $attributes = $extractor->getComponentProperties($metadata);

        $this->assertEmpty($attributes);
    }
}
