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

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Notify\NotifyBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class TwigAppKernel extends Kernel
{
    public function __construct(
        string $environment,
        bool $debug,
        private readonly string $mercureHub = 'mercure.hub.default',
    ) {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new StimulusBundle();
        yield new MercureBundle();
        yield new NotifyBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $mercureHub = $this->mercureHub;

        $loader->load(static function (ContainerBuilder $container) use ($mercureHub) {
            $container->loadFromExtension('framework', [
                'secret' => '$ecret',
                'test' => true,
                'http_method_override' => false,
                'php_errors' => [
                    'log' => true,
                ],
                ...(self::VERSION_ID >= 60200 ? [
                    'handle_all_throwables' => true,
                ] : []),
            ]);
            $container->loadFromExtension('twig', [
                'default_path' => __DIR__.'/templates',
                'strict_variables' => true,
            ]);
            $hubs = [
                'default' => [
                    'url' => 'http://localhost:9090/.well-known/mercure',
                    'public_url' => 'http://localhost:9090/.well-known/mercure',
                    'jwt' => [
                        'secret' => '$ecret',
                        'publish' => '*',
                    ],
                ],
            ];

            // MercureBundle 0.6 defaults "protocol_version" to "1.0", and its "jwt.secret" hubs then require the RFC 9068 claims.
            if (self::supportsProtocolVersion()) {
                $hubs['default']['protocol_version'] = '0.x';
                $hubs['v1'] = [
                    'url' => $hubs['default']['url'],
                    'protocol_version' => '1.0',
                    'jwt' => [
                        ...$hubs['default']['jwt'],
                        'claims' => ['iss' => 'https://example.com', 'sub' => 'test', 'client_id' => 'test'],
                    ],
                ];
            }

            $container->loadFromExtension('mercure', ['hubs' => $hubs]);
            $container->loadFromExtension('notify', ['mercure_hub' => $mercureHub]);

            $container->setAlias('test.notify.twig_runtime', 'notify.twig_runtime')->setPublic(true);
        });
    }

    public function getCacheDir(): string
    {
        return $this->createTmpDir('cache');
    }

    public function getLogDir(): string
    {
        return $this->createTmpDir('logs');
    }

    // MercureBundle 0.5 introduced "protocol_version". Checking the installed symfony/mercure instead
    // would be wrong: MercureBundle 0.4.1 and older declare no constraint on the component.
    public static function supportsProtocolVersion(): bool
    {
        return InstalledVersions::satisfies(new VersionParser(), 'symfony/mercure-bundle', '>=0.5');
    }

    private function createTmpDir(string $type): string
    {
        $dir = sys_get_temp_dir().'/notify_bundle/'.uniqid($type.'_', true);

        if (!file_exists($dir)) {
            mkdir($dir, 0o777, true);
        }

        return $dir;
    }
}
