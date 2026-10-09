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
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\UX\Turbo\TurboFrame;

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

        $hubs = [
            // Speaks whatever protocol MercureBundle defaults to: "0.x" up to MercureBundle 0.5, "1.0" since 0.6
            'default' => [
                'url' => 'http://127.0.0.1:3000/.well-known/mercure',
                'jwt' => 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.vhMwOaN5K68BTIhWokMLOeOJO4EPfT64brd8euJOA4M',
            ],
        ];
        // The hub the protocol 0.x tests target, so that they do not depend on that default
        $hubs['legacy'] = $hubs['default'];

        if (enum_exists(ProtocolVersion::class)) {
            $hubs['legacy']['protocol_version'] = '0.x';

            // A hub speaking the Mercure protocol 1.0 (symfony/mercure 0.8+)
            $hubs['v1'] = [
                'url' => 'http://127.0.0.1:3000/.well-known/mercure',
                'protocol_version' => '1.0',
                'jwt' => [
                    'secret' => '!ChangeThisMercureHubJWTSecretKey!',
                    'publish' => '*',
                    'claims' => ['iss' => 'https://example.com', 'sub' => 'test', 'client_id' => 'test'],
                ],
            ];
        }
        $container->extension('mercure', ['hubs' => $hubs]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('turbo_request', '/turboRequest')->controller('kernel::turboRequest');
        $routes->add('turbo_frame_request', '/turboFrameRequest')->controller('kernel::turboFrameRequest');
    }

    public function turboRequest(Request $request): Response
    {
        return new JsonResponse([
            'preferred_format' => $request->getPreferredFormat(),
        ]);
    }

    public function turboFrameRequest(TurboFrame $turboFrame): Response
    {
        return new JsonResponse([
            'turbo_is_frame_request' => $turboFrame->isFrameRequest(),
            'turbo_frame_request_id' => $turboFrame->getRequestId(),
        ]);
    }
}
