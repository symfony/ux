<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Map\UXMapBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @internal
 */
class TwigComponentKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new StimulusBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UXMapBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => '$ecret',
                'test' => true,
                'http_method_override' => false,
            ]);
            $container->loadFromExtension('twig', [
                'default_path' => __DIR__.'/templates',
                'strict_variables' => true,
                'exception_controller' => null,
            ]);
            $container->loadFromExtension('twig_component', [
                'defaults' => [],
                'anonymous_template_directory' => 'components',
            ]);
            $container->loadFromExtension('ux_map', []);

            $container->setAlias('test.ux_map.renderers', 'ux_map.renderers')->setPublic(true);
        });
    }
}
