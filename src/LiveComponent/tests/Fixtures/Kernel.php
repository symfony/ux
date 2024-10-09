<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures;

use Composer\InstalledVersions;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Serializer\Entity2Normalizer;
use Symfony\UX\LiveComponent\Tests\Fixtures\Serializer\MoneyNormalizer;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function index(): Response
    {
        return new Response('index');
    }

    public function renderTemplate(string $template, ?Environment $twig = null): Response
    {
        $twig ??= $this->container->get('twig');

        return new Response($twig->render("{$template}.html.twig"));
    }

    public function renderNamespacedTemplate(string $template, ?Environment $twig = null): Response
    {
        $twig ??= $this->container->get('twig');

        return new Response($twig->render('@'.FilesystemLoader::MAIN_NAMESPACE.'/'.$template.'.html.twig'));
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
        yield new SecurityBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new StimulusBundle();
        yield new ZenstruckFoundryBundle();
    }

    protected function build(ContainerBuilder $container): void
    {
        // workaround https://github.com/symfony/symfony/issues/50322
        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->removeDefinition('doctrine.orm.listeners.pdo_session_handler_schema_listener');
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $frameworkConfig = [
            'csrf_protection' => ['enabled' => false],
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'http_method_override' => false,
            'property_info' => ['enabled' => true],
            'php_errors' => ['log' => true],
            'validation' => [
                'email_validation_mode' => 'html5',
            ],
        ];

        if (self::VERSION_ID >= 60400) {
            $frameworkConfig['handle_all_throwables'] = true;
            $frameworkConfig['session'] = [
                'storage_factory_id' => 'session.storage.factory.mock_file',
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax',
                'handler_id' => null,
            ];
            $frameworkConfig['annotations']['enabled'] = false;
        }

        $c->extension('framework', $frameworkConfig);

        $c->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $security = [
            'password_hashers' => [InMemoryUser::class => 'plaintext'],
            'providers' => ['users' => ['memory' => ['users' => ['kevin' => ['password' => 'pass', 'roles' => ['ROLE_USER']]]]]],
            'firewalls' => ['main' => [
                'lazy' => true,
            ]],
        ];

        if (!class_exists(Security::class)) {
            $security['enable_authenticator_manager'] = true;
        }

        $c->extension('security', $security);

        $c->extension('twig_component', [
            'defaults' => [
                'Symfony\UX\LiveComponent\Tests\Fixtures\Component\\' => 'components/',
            ],
            'anonymous_template_directory' => 'components/',
        ]);

        $c->extension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        $doctrineConfig = [
            'dbal' => [
                'url' => '%env(resolve:DATABASE_URL)%',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Default' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                        'prefix' => 'Symfony\UX\LiveComponent\Tests\Fixtures\Entity',
                        'alias' => 'Default',
                    ],
                    'XML' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => '%kernel.project_dir%/tests/Fixtures/config/doctrine',
                        'prefix' => 'Symfony\UX\LiveComponent\Tests\Fixtures\Dto',
                        'alias' => 'XML',
                    ],
                ],
            ],
        ];

        if (null !== $doctrineBundleVersion = InstalledVersions::getVersion('doctrine/doctrine-bundle')) {
            if (version_compare($doctrineBundleVersion, '2.8.0', '>=')) {
                $doctrineConfig['orm']['enable_lazy_ghost_objects'] = true;
            }
            // https://github.com/doctrine/DoctrineBundle/pull/1661
            if (version_compare($doctrineBundleVersion, '2.9.0', '>=')) {
                $doctrineConfig['orm']['report_fields_where_declared'] = true;
                $doctrineConfig['orm']['validate_xml_mapping'] = true;
                $doctrineConfig['dbal']['schema_manager_factory'] = 'doctrine.dbal.default_schema_manager_factory';
            }
            if (version_compare($doctrineBundleVersion, '2.12.0', '>=')) {
                $doctrineConfig['orm']['controller_resolver']['auto_mapping'] = false;
            }
        }

        $c->extension('doctrine', $doctrineConfig);

        $c->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
            // disable logging errors to the console
            ->set('logger', NullLogger::class)
            ->set(MoneyNormalizer::class)->autoconfigure()->autowire()
            ->set(Entity2Normalizer::class)->autoconfigure()->autowire()
            ->load(__NAMESPACE__.'\\Component\\', __DIR__.'/Component')
            ->set(TestingDeterministicIdTwigExtension::class)
            ->args([service('ux.live_component.deterministic_id_calculator')])
            ->set('some_service_id', Component1::class)
                ->tag('twig.component', ['live' => true])
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@LiveComponentBundle/config/routes.php')
            ->prefix('/_components');

        $routes->add('template', '/render-template/{template}')->controller('kernel::renderTemplate');
        $routes->add('render_namespaced_template', '/render-namespaced-template/{template}')->controller('kernel::renderNamespacedTemplate');
        $routes->add('homepage', '/')->controller('kernel::index');
        $routes->add('alternate_live_route', '/alt/{_live_component}/{_live_action}')->defaults(['_live_action' => 'get']);
        $routes->add('localized_route', '/locale/{_locale}/{_live_component}/{_live_action}')->defaults(['_live_action' => 'get']);
    }
}
