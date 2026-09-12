<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Tests\Fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Inspector\UXInspectorBundle;

final class InspectorKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(
        string $environment,
        bool $debug,
        private readonly string $directory,
        private readonly bool $inspector = true,
        private readonly bool $enabled = true,
        private readonly bool $assetMapper = false,
        private readonly string $routePrefix = '',
        private readonly bool $pullTab = true,
    ) {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        if ($this->inspector) {
            yield new UXInspectorBundle();
        }
    }

    public function getProjectDir(): string
    {
        return $this->directory;
    }

    public function getCacheDir(): string
    {
        return $this->directory.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->directory.'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->services()->set('logger', NullLogger::class);
        // Only testDoesNotRegisterAnAssetMapperPath() needs AssetMapper: the
        // Inspector is served by a route and never touches the importmap.
        if ($this->assetMapper) {
            $container->services()->alias('test.asset_mapper', 'asset_mapper')->public();
        }
        $container->extension('framework', [
            'secret' => 'inspector-test',
            'http_method_override' => false,
            'router' => ['utf8' => true],
            'asset_mapper' => ['enabled' => $this->assetMapper, 'paths' => []],
        ]);
        if ($this->inspector) {
            $container->extension('ux_inspector', ['enabled' => $this->enabled, 'pull_tab' => $this->pullTab]);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        if ($this->inspector) {
            $routes->import(__DIR__.'/../../config/routes.php')->prefix($this->routePrefix);
        }
        $routes->add('page', '/')->controller([self::class, 'page']);
    }

    public function page(): Response
    {
        return new Response('<!DOCTYPE html><html><head><title>Inspector</title></head><body><main data-controller="hello">Hello</main></body></html>', headers: ['Content-Type' => 'text/html']);
    }
}
