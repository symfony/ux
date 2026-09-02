<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginatorInterface;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Author;
use Symfony\UX\Pagination\Tests\Fixtures\EntityManagerFactory;
use Symfony\UX\Pagination\Tests\Fixtures\LiveAutoRoutePaginationComponent;
use Symfony\UX\Pagination\Tests\Fixtures\LivePaginationComponent;
use Symfony\UX\Pagination\Twig\PaginationExtension;
use Symfony\UX\Pagination\Twig\PaginationRenderer;
use Symfony\UX\Pagination\UXPaginationBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;

/**
 * Boots a real kernel with FrameworkBundle + TwigBundle + UXPaginationBundle
 * and exercises the whole chain: container wiring, adapter discovery,
 * Twig rendering with translations.
 */
#[CoversNothing]
final class UXPaginationIntegrationTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    protected static function getKernelClass(): string
    {
        return UXPaginationTestKernel::class;
    }

    public function testPaginatorServiceIsAutowirable()
    {
        self::bootKernel();

        $paginator = self::getContainer()->get(PaginatorInterface::class);

        self::assertInstanceOf(PaginatorInterface::class, $paginator);
    }

    public function testNamedPaginatorSupportsSymfonyAutowiringConventions()
    {
        self::bootKernel();

        $consumer = self::getContainer()->get(NamedPaginatorConsumer::class);

        self::assertSame($consumer->blogPaginator, $consumer->targetedPaginator);
        self::assertSame($consumer->blogPaginator, $consumer->explicitPaginator);

        $pagination = $consumer->blogPaginator->paginate(range(1, 120), page: 5);
        self::assertSame(12, $pagination->getItemsPerPage());
        self::assertSame('p', $pagination->getPageParameterName());
        self::assertCount(8, $pagination->getPages());
    }

    public function testPaginatorIsInjectedIntoAControllerAction()
    {
        $kernel = self::bootKernel(['environment' => 'controller_injection']);
        $request = Request::create('/_ux-pagination/controller');
        $response = $kernel->handle($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Symfony\UX\Pagination\Paginator', $response->getContent());

        $kernel->terminate($request, $response);
    }

    public function testRendererServiceIsConfigured()
    {
        self::bootKernel();

        self::assertInstanceOf(
            PaginationRenderer::class,
            self::getContainer()->get('ux_pagination.renderer'),
        );
    }

    public function testTwigRegistersThePaginationFunctions()
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        self::assertNotNull($twig->getFunction('ux_pagination'));
        self::assertNull($twig->getFunction('pagination_head'));
    }

    public function testArrayAdapterIsWired()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);

        $pagination = $paginator->paginate(range(1, 45), page: 2, perPage: 10);

        self::assertInstanceOf(Pagination::class, $pagination);
        self::assertSame(range(11, 20), $pagination->getItems());
        self::assertSame(45, $pagination->getTotalItems());
        self::assertSame(5, $pagination->getTotalPages());
    }

    public function testDoctrineOrmAdapterIsTagged()
    {
        if (!class_exists(\Doctrine\ORM\QueryBuilder::class)) {
            self::markTestSkipped('Doctrine ORM is not installed.');
        }

        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('ux_pagination.adapter.doctrine_orm'));
    }

    public function testDoctrineDbalAdapterIsTagged()
    {
        if (!class_exists(\Doctrine\DBAL\Query\QueryBuilder::class)) {
            self::markTestSkipped('Doctrine DBAL is not installed.');
        }

        self::bootKernel();

        self::assertTrue(self::getContainer()->has('ux_pagination.adapter.doctrine_dbal'));
    }

    public function testTwigFunctionRendersNavigationWithTranslations()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 100))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 3);

        $html = $twig->getRuntime(PaginationExtension::class)->renderPagination($pagination);

        self::assertMatchesRegularExpression('/<nav[^>]+class="ux-pagination/', $html);
        self::assertStringNotContainsString('data-controller=', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('Previous', $html);
        self::assertStringContainsString('Next', $html);
        self::assertStringNotContainsString('pagination.previous', $html);
        self::assertStringContainsString('21-30', $html);
        self::assertStringContainsString('100', $html);
    }

    public function testBootstrapThemeRenders()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 1);

        $html = $twig->getRuntime(PaginationExtension::class)->renderPagination(
            $pagination,
            theme: '@UXPagination/theme/bootstrap.html.twig',
        );

        self::assertStringContainsString('pagination', $html);
        self::assertStringContainsString('ux-pagination-bootstrap', $html);
    }

    public function testConfiguredThemeIsUsedByDefault()
    {
        self::bootKernel(['environment' => 'bootstrap_theme']);

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 2);

        $html = $twig
            ->getRuntime(PaginationExtension::class)
            ->renderPagination($pagination);

        self::assertStringContainsString('ux-pagination-bootstrap', $html);
        self::assertStringNotContainsString('ux-pagination-tailwind', $html);
    }

    public function testExplicitThemeOverridesConfiguredTheme()
    {
        self::bootKernel(['environment' => 'bootstrap_theme']);

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 2);

        $html = $twig
            ->getRuntime(PaginationExtension::class)
            ->renderPagination(
                $pagination,
                theme: '@UXPagination/theme/tailwind.html.twig',
            );

        self::assertStringContainsString('ux-pagination-tailwind', $html);
        self::assertStringNotContainsString('ux-pagination-bootstrap', $html);
    }

    public function testDocumentedTwigComponentRenders()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 2);

        $html = $twig->createTemplate(<<<'TWIG'
            <twig:ux:pagination
                :pagination="pagination"
                theme="@UXPagination/theme/tailwind.html.twig"
                class="product-pages"
                :navigationAttributes="{class: 'product-pages__controls'}"
                :linkAttributes="{class: 'product-pages__link'}"
            />
            TWIG)
            ->render(['pagination' => $pagination]);

        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('ux-pagination-tailwind', $html);
        self::assertStringContainsString('product-pages', $html);
        self::assertStringContainsString('product-pages__controls', $html);
        self::assertStringContainsString('product-pages__link', $html);
        self::assertStringNotContainsString('theme=', $html);
    }

    public function testTwigComponentUsesTheConfiguredTheme()
    {
        self::bootKernel(['environment' => 'bootstrap_theme']);

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 2);

        $html = $twig->createTemplate(
            '<twig:ux:pagination :pagination="pagination" />',
        )->render(['pagination' => $pagination]);

        self::assertStringContainsString('ux-pagination-bootstrap', $html);
        self::assertStringNotContainsString('ux-pagination-tailwind', $html);
    }

    public function testTwigComponentThemeOverridesTheConfiguredTheme()
    {
        self::bootKernel(['environment' => 'bootstrap_theme']);

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 50))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 2);

        $html = $twig->createTemplate(<<<'TWIG'
            <twig:ux:pagination
                :pagination="pagination"
                theme="@UXPagination/theme/tailwind.html.twig"
            />
            TWIG)
            ->render(['pagination' => $pagination]);

        self::assertStringContainsString('ux-pagination-tailwind', $html);
        self::assertStringNotContainsString('ux-pagination-bootstrap', $html);
    }

    public function testInfoFallsBackToEnglishWithoutTranslator()
    {
        self::bootKernel(['environment' => 'no_translator']);

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);

        $pagination = $paginator->paginate(range(1, 100), page: 3, perPage: 10);

        self::assertSame('Showing 21-30 of 100', $pagination->getInfo());
    }

    public function testPaginatesRealQueryBuilderThroughContainerService()
    {
        if (!class_exists(\Doctrine\ORM\EntityManager::class)) {
            self::markTestSkipped('Doctrine ORM is not installed.');
        }

        self::bootKernel();

        $entityManager = EntityManagerFactory::create();
        for ($i = 1; $i <= 12; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $entityManager->persist($author);
        }
        $entityManager->flush();

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->orderBy('a.id', 'ASC');

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);

        $pagination = $paginator->paginate($queryBuilder, page: 2, perPage: 5);

        self::assertSame(12, $pagination->getTotalItems());
        self::assertSame(3, $pagination->getTotalPages());
        self::assertSame([6, 7, 8, 9, 10], array_map(static fn (Author $a) => $a->getId(), $pagination->getItems()));
    }

    public function testDefaultTemplateRendersCursorPagination()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $source = array_map(static fn (int $i) => ['id' => $i], range(1, 30));
        $pagination = $paginator->cursor($source)
            ->orderBy('id', 'ASC')
            ->context('events')
            ->perPage(10)
            ->path('/items')
            ->paginate();

        $html = $twig->getRuntime(PaginationExtension::class)->renderPagination($pagination);

        self::assertStringContainsString('class="ux-pagination"', $html);
        self::assertStringContainsString('cursor=', $html);
        self::assertStringContainsString('Next', $html);
    }

    public function testComponentWithPaginationTraitHandlesARealLiveComponentAction()
    {
        self::bootKernel();

        $component = $this->createLiveComponent('live_pagination');
        $initialHtml = (string) $component->render();

        self::assertStringContainsString('data-controller="live"', $initialHtml);
        self::assertStringContainsString('<p data-current-page>1</p>', $initialHtml);
        self::assertStringContainsString('href="/products?page=2"', $initialHtml);
        self::assertStringContainsString('data-live-page-param="2"', $initialHtml);

        $component->call('goToPage', ['page' => 2]);
        $updatedHtml = (string) $component->render();

        self::assertTrue($component->response()->isSuccessful());
        self::assertStringContainsString('<p data-current-page>2</p>', $updatedHtml);
        self::assertStringContainsString('href="/products"', $updatedHtml);
        self::assertStringContainsString('aria-current="page">', $updatedHtml);
        self::assertSame(2, $component->component()->page);
    }

    public function testBundleTemplateOverrideAppliesToTheDefaultTheme()
    {
        self::bootKernel();

        /** @var PaginatorInterface $paginator */
        $paginator = self::getContainer()->get(PaginatorInterface::class);
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $pagination = $paginator->query(range(1, 100))
            ->perPage(10)
            ->path('/items')
            ->paginate(page: 3);

        $html = $twig->getRuntime(PaginationExtension::class)->renderPagination($pagination);

        self::assertStringContainsString('<!--app-label-override-->', $html);
        self::assertStringContainsString('Previous', $html);
    }

    public function testCapturedRouteKeepsLinkUrlsStableAcrossLiveRerenders()
    {
        self::bootKernel();

        $component = $this->createLiveComponent('live_pagination_auto', [
            'paginationRoute' => 'ux_pagination_test_products',
        ]);

        $initialHtml = (string) $component->render();
        self::assertStringContainsString('href="/products?page=2"', $initialHtml);

        $component->call('goToPage', ['page' => 2]);
        $updatedHtml = (string) $component->render();

        self::assertTrue($component->response()->isSuccessful());
        self::assertStringContainsString('href="/products?page=3"', $updatedHtml);
        self::assertStringContainsString('href="/products"', $updatedHtml);
        self::assertStringNotContainsString('href="/_components', $updatedHtml);
    }
}

final class UXPaginationTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new StimulusBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new UXPaginationBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true);
            }
            $container->getDefinition('kernel')->addTag('routing.route_loader');
            $container->loadFromExtension('framework', [
                'secret' => 'test-secret',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
                'translator' => 'no_translator' === $this->environment
                    ? ['enabled' => false]
                    : ['fallbacks' => ['en']],
            ]);
            $container->loadFromExtension('twig_component', [
                'defaults' => [],
                'anonymous_template_directory' => 'components/',
            ]);
            $container->loadFromExtension('twig', [
                'default_path' => __DIR__.'/../Fixtures/templates',
            ]);
            $paginationConfig = [
                'items_per_page' => 30,
                'page_parameter' => 'page',
                'paginators' => [
                    'blog' => [
                        'items_per_page' => 12,
                        'page_parameter' => 'p',
                        'navigation' => [
                            'mode' => 'fixed',
                            'size' => 2,
                        ],
                    ],
                ],
            ];
            if ('bootstrap_theme' === $this->environment) {
                $paginationConfig['theme'] =
                    '@UXPagination/theme/bootstrap.html.twig';
            }
            $container->loadFromExtension(
                'ux_pagination',
                $paginationConfig,
            );
            $container->register(TestPaginationController::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->setPublic(true)
                ->addTag('controller.service_arguments');
            $container->register(NamedPaginatorConsumer::class)
                ->setAutowired(true)
                ->setPublic(true);
            $container->register(LivePaginationComponent::class)
                ->setAutoconfigured(true)
                ->setAutowired(true);
            $container->register(LiveAutoRoutePaginationComponent::class)
                ->setAutoconfigured(true)
                ->setAutowired(true);
        });
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('ux_pagination_test_controller', '/_ux-pagination/controller')
            ->controller(TestPaginationController::class.'::__invoke');
        $routes->add('ux_pagination_test_products', '/products');
        $routes->import('@LiveComponentBundle/config/routes.php')
            ->prefix('/_components');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux_pagination_tests/cache/'.Kernel::VERSION_ID.'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux_pagination_tests/log/'.Kernel::VERSION_ID;
    }
}

final class TestPaginationController
{
    public function __invoke(PaginatorInterface $paginator): Response
    {
        return new Response($paginator::class);
    }
}

final class NamedPaginatorConsumer
{
    public function __construct(
        public readonly PaginatorInterface $blogPaginator,
        #[Target('blog')]
        public readonly PaginatorInterface $targetedPaginator,
        #[Autowire(service: 'ux_pagination.paginator.blog')]
        public readonly PaginatorInterface $explicitPaginator,
    ) {
    }
}
