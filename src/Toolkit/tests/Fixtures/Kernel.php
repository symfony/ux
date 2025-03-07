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
use Symfony\UX\Toolkit\UxToolkitBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle;

/**
 * @author Jean-François Lépine
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UxToolkitBundle(),
            new TalesFromADevTwigExtraTailwindBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $containerConfigurator): void
    {
        $config = [
            'secret' => 'SECRET',
            'test' => true,
        ];

        $containerConfigurator->extension('framework', $config);
        $containerConfigurator->extension('twig', [
            'default_path' => __DIR__.'/../../templates/default',
        ]);

        $config = [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ];

        $containerConfigurator->extension('twig_component', $config);
    }
}
