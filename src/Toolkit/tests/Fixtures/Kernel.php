<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\Toolkit\UXToolkitBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UXToolkitBundle(),
            new TalesFromADevTwigExtraTailwindBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'http_method_override' => false,
            'php_errors' => ['log' => true],
            'property_access' => true,
            'http_client' => true,
            'handle_all_throwables' => true,
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/../../kits',
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ]);

        $container->services()
            ->alias('ux_toolkit.kit.factory', '.ux_toolkit.kit.factory')
                ->public()

            ->alias('ux_toolkit.kit.dependencies_resolver', '.ux_toolkit.dependency.dependencies_resolver')
                ->public()

            ->alias('ux_toolkit.registry.factory', '.ux_toolkit.registry.factory')
                ->public()
        ;
    }
}
