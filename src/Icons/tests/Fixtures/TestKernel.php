<?php

namespace Symfony\UX\Icons\Tests\Fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new UXIconsBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'http_method_override' => false,
            'php_errors' => ['log' => true],
            'property_access' => true,
            'http_client' => true,
        ]);

        $c->extension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);

        $c->extension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components',
        ]);

        $c->extension('ux_icons', [
            'icon_dir' => '%kernel.project_dir%/tests/Fixtures/icons',
        ]);

        $c->services()->set('logger', NullLogger::class);
    }
}
