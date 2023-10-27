<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The template can be updated by PreRender event listeners.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComponentEventTest extends KernelTestCase
{
    /**
     * @dataProvider provideFooBarSyntaxes
     */
    public function testTemplateIsUpdatedByEventListener(string $syntax): void
    {
        /** @var Environment $environment */
        $environment = self::getContainer()->get(Environment::class);
        $environment->setLoader(new ArrayLoader([
            'components/FooBar/Baz.foo_bar.html.twig' => 'updated',
            'components/FooBar/Baz.html.twig' => 'original',
        ]));

        $component = $environment->createTemplate($syntax);
        $result = $component->render();

        self::assertSame('updated', $result);
    }

    public static function provideFooBarSyntaxes(): iterable
    {
        yield 'TWIG component tag' => ['{% component "FooBar:Baz" %}{% endcomponent %}'];
        yield 'TWIG component function' => ['{{ component("FooBar:Baz") }}'];
        yield 'HTML self-closing tag' => ['<twig:FooBar:Baz />'];
        yield 'HTML open-close tag' => ['<twig:FooBar:Baz></twig:FooBar:Baz>'];
    }
}
