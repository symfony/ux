<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TemplateWrapper;

/**
 * The "component" tag must accept any valid "name" argument (in Twig & HTML syntax).
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComponentParserTest extends KernelTestCase
{
    /**
     * @dataProvider provideValidComponentNames
     */
    public function testAcceptTwigComponentTagWithValidComponentName(string $name): void
    {
        $environment = self::createEnvironment();
        $source = str_replace('XXX', $name, "{% component 'XXX' %}{% endcomponent %}");

        $template = $environment->createTemplate($source);

        self::assertInstanceOf(TemplateWrapper::class, $template);
    }

    /**
     * @dataProvider provideValidComponentNames
     */
    public function testAcceptHtmlComponentTagWithValidComponentName(string $name): void
    {
        $environment = self::createEnvironment();
        $source = sprintf('<twig:%s></twig:%s>', $name, $name);

        $template = $environment->createTemplate($source);

        self::assertInstanceOf(TemplateWrapper::class, $template);
    }

    /**
     * @dataProvider provideValidComponentNames
     */
    public function testAcceptHtmlSelfClosingComponentTagWithValidComponentName(string $name): void
    {
        $environment = self::createEnvironment();
        $source = sprintf('<twig:%s />', $name);

        $template = $environment->createTemplate($source);

        self::assertInstanceOf(TemplateWrapper::class, $template);
    }

    public static function provideValidComponentNames(): iterable
    {
        // Those names are all syntactically valid even if
        // they do not match any component class or template
        $names = [
            'Nope',
            'NopeNope',
            'Nope:Nope',
            'Nope6',
        ];

        foreach ($names as $name) {
            yield $name => [$name];
        }
    }

    private function createEnvironment(): Environment
    {
        /** @var Environment $environment */
        $environment = self::getContainer()->get(Environment::class);
        $environment->setLoader(new ArrayLoader());

        return $environment;
    }
}
