<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Minimal kernel used by Turbo PHPUnit tests.
 *
 * Browser-based scenarios live in `apps/e2e/` and are exercised through
 * Playwright; this kernel only registers what is required by tests under
 * `src/Turbo/tests/`.
 *
 * @internal
 */
final class FrameworkAppKernel extends Kernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux_turbo/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux_turbo/logs';
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new MercureBundle();
        yield new StimulusBundle();
        yield new TurboBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ChangeMe',
            'test' => 'test' === $this->environment,
            'router' => [
                'utf8' => true,
            ],
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
        ]);

        $container->extension('mercure', [
            'hubs' => [
                'default' => [
                    'url' => 'http://127.0.0.1:3000/.well-known/mercure',
                    'jwt' => 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.vhMwOaN5K68BTIhWokMLOeOJO4EPfT64brd8euJOA4M',
                ],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('turbo_request', '/turboRequest')->controller('kernel::turboRequest');
    }

    public function turboRequest(Request $request): Response
    {
        return new JsonResponse([
            'preferred_format' => $request->getPreferredFormat(),
        ]);
    }
}
