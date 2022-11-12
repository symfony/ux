<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Notify\NotifyBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class TwigAppKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new MercureBundle();
        yield new WebpackEncoreBundle();
        yield new NotifyBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', ['secret' => '$ecret', 'test' => true, 'http_method_override' => false]);
            $container->loadFromExtension('twig', ['default_path' => __DIR__.'/templates', 'strict_variables' => true, 'exception_controller' => null]);
            $container->loadFromExtension('webpack_encore', ['output_path' => '%kernel.project_dir%/public/build']);
            $container->loadFromExtension('mercure', [
                'hubs' => [
                    'default' => [
                        'url' => 'http://localhost:9090/.well-known/mercure',
                        'public_url' => 'http://localhost:9090/.well-known/mercure',
                        'jwt' => [
                            'secret' => '$ecret',
                            'publish' => '*',
                        ],
                    ],
                ],
            ]);

            $container->setAlias('test.notify.twig_runtime', 'notify.twig_runtime')->setPublic(true);
        });
    }
}
