<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\UX\Toolkit\DependencyInjection\AnonymousTemplateDirectoryPass;

final class AnonymousTemplateDirectoryPassTest extends TestCase
{
    public function testPassesTheTwigComponentAnonymousTemplateDirectoryToTheInstallCommand(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('ux.twig_component.component_template_finder', new Definition(null, [null, 'twig_components']));
        $container->setDefinition('.ux_toolkit.command.install', $command = new Definition(null, [null, null, null]));

        new AnonymousTemplateDirectoryPass()->process($container);

        $this->assertSame('twig_components', $command->getArgument('$anonymousTemplateDirectory'));
    }

    public function testKeepsTheDefaultWithoutTwigComponent(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_toolkit.command.install', $command = new Definition(null, [null, null, null]));

        new AnonymousTemplateDirectoryPass()->process($container);

        $this->assertSame([null, null, null], $command->getArguments());
    }
}
