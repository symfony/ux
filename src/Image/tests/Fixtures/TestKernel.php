<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Image\UXImageBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new UXImageBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'http_method_override' => false,
            'php_errors' => ['log' => true],
            ...(self::VERSION_ID >= 60200 ? ['handle_all_throwables' => true] : []),
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
            'strict_variables' => true,
        ]);

        $container->extension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components',
        ]);

        $container->extension('ux_image', match ($this->environment) {
            'no_auto_format' => ['provider' => 'fake://default?auto_format=0'],
            'bare' => [],
            'env_placeholder' => ['provider' => '%env(UX_IMAGE_DSN)%'],
            default => ['provider' => 'fake://default'],
        });

        if ('bare' !== $this->environment) {
            $container->services()
                ->set('test.ux_image.provider_factory.fake', FakeProviderFactory::class)
                    ->tag('ux_image.provider_factory')
            ;
        }
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux_image_bundle/cache/'.self::VERSION_ID.'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux_image_bundle/log/'.self::VERSION_ID;
    }
}
