<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Threejs\ThreejsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
class TwigAppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new StimulusBundle(), new ThreeJsBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', ['secret' => '$ecret', 'test' => true, 'http_method_override' => false]);
            $container->loadFromExtension('twig', ['default_path' => __DIR__.'/templates', 'strict_variables' => true, 'exception_controller' => null]);

            $container->setAlias('test.threejs.twig_extension', 'threejs.twig_extension')->setPublic(true);
        });
    }
}
