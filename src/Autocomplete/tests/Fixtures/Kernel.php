<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\UX\Autocomplete\AutocompleteBundle;
use Symfony\UX\Autocomplete\DependencyInjection\AutocompleteFormTypePass;
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomGroupByProductAutocompleter;
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomProductAutocompleter;
use Symfony\UX\Autocomplete\Tests\Fixtures\Form\ProductType;
use Twig\Environment;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private bool $enableForms = true;

    public function disableForms(): void
    {
        $this->enableForms = false;
    }

    public function testForm(FormFactoryInterface $formFactory, Environment $twig, Request $request): Response
    {
        $form = $formFactory->create(ProductType::class);
        $form->handleRequest($request);

        return new Response($twig->render('form.html.twig', [
            'form' => $form->createView()
        ]));
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
        yield new AutocompleteBundle();
        yield new SecurityBundle();
        yield new MakerBundle();
        yield new ZenstruckFoundryBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => 'S3CRET',
            'http_method_override' => false,
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'form' => ['enabled' => $this->enableForms],
        ]);

        $c->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $c->extension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        $c->extension('doctrine', [
            'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Test' => [
                        'is_bundle' => false,
                        'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                        'prefix' => 'Symfony\UX\Autocomplete\Tests\Fixtures\Entity',
                        'type' => 'attribute',
                        'alias' => 'Test',
                    ],
                ],
            ],
        ]);

        $c->extension('security', [
            'enable_authenticator_manager' => true,
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'plaintext'
            ],
            'providers' => [
                'users_in_memory' => [
                    'memory' => [
                        'users' => [
                            'mr_autocompleter' => ['password' => 'symfonypass', 'roles' => ['ROLE_USER']]
                        ],
                    ],
                ]
            ],
            'firewalls' => [
                'main' => [
                    'http_basic' => true,
                ],
            ],
        ]);

        $c->extension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        $services = $c->services();
        $services
            ->defaults()
                ->autowire()
                ->autoconfigure()
            // disable logging errors to the console
            ->set('logger', NullLogger::class)
            ->load(__NAMESPACE__.'\\', __DIR__)
            ->exclude(['Kernel.php'])
        ;

        $services->set(CustomProductAutocompleter::class)
            ->public()
            ->arg(1, new Reference('ux.autocomplete.entity_search_util'))
            ->tag(AutocompleteFormTypePass::ENTITY_AUTOCOMPLETER_TAG, [
                'alias' => 'custom_product'
            ]);

        $services->set(CustomGroupByProductAutocompleter::class)
            ->public()
            ->arg(1, new Reference('ux.autocomplete.entity_search_util'))
            ->tag(AutocompleteFormTypePass::ENTITY_AUTOCOMPLETER_TAG, [
                'alias' => 'custom_group_by_product'
            ]);

        $services->alias('public.results_executor', 'ux.autocomplete.results_executor')
            ->public();

        $services->alias('public.ux.autocomplete.make_autocomplete_field', 'ux.autocomplete.make_autocomplete_field')
            ->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@AutocompleteBundle/config/routes.php')
            ->prefix('/test/autocomplete');

        $routes->add('test_form', '/test-form')->controller('kernel::testForm');

        $routes->add('ux_autocomplete_alternate', '/alt/test/autocomplete/{alias}')
            ->controller('ux.autocomplete.entity_autocomplete_controller');
    }
}
