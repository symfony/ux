<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Navigation\NavigationMode;
use Symfony\UX\Pagination\PaginatorInterface;
use Symfony\UX\Pagination\UXPaginationBundle;

#[CoversClass(UXPaginationBundle::class)]
final class UXPaginationExtensionTest extends TestCase
{
    public function testLoadInjectsDefaultConfigurationWithoutExposingParameters()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        foreach (array_keys($container->getParameterBag()->all()) as $parameter) {
            self::assertStringStartsNotWith('ux_pagination.', $parameter, \sprintf('Parameter "%s" should not be exposed.', $parameter));
        }

        $paginator = $container->getDefinition('ux_pagination.paginator');
        self::assertSame(20, $paginator->getArgument('$defaultPerPage'));
        self::assertSame(100_000, $paginator->getArgument('$defaultMaxOffset'));
        self::assertSame('page', $paginator->getArgument('$defaultPageParam'));
        self::assertSame('cursor', $paginator->getArgument('$defaultCursorParam'));
        self::assertSame(NavigationMode::Sliding, $paginator->getArgument('$defaultNavigationMode'));
        self::assertSame(5, $paginator->getArgument('$defaultNavigationSize'));
        $cursorSecret = $container->getDefinition('ux_pagination.cursor_codec')->getArgument('$secret');
        self::assertSame('%kernel.secret%', $cursorSecret);
    }

    public function testLoadInjectsCustomConfiguration()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', ['TwigBundle' => TwigBundle::class]);
        $extension = $this->getExtension();

        $extension->load([
            [
                'items_per_page' => 50,
                'max_offset' => 250_000,
                'page_parameter' => 'p',
                'cursor_parameter' => 'after',
                'navigation' => ['mode' => 'fixed', 'size' => 9],
                'theme' => '@UXPagination/theme/tailwind.html.twig',
                'cursor' => ['secret' => 'pagination-secret'],
            ],
        ], $container);

        $paginator = $container->getDefinition('ux_pagination.paginator');
        self::assertSame(50, $paginator->getArgument('$defaultPerPage'));
        self::assertSame(250_000, $paginator->getArgument('$defaultMaxOffset'));
        self::assertSame('p', $paginator->getArgument('$defaultPageParam'));
        self::assertSame('after', $paginator->getArgument('$defaultCursorParam'));
        self::assertSame(NavigationMode::Fixed, $paginator->getArgument('$defaultNavigationMode'));
        self::assertSame(9, $paginator->getArgument('$defaultNavigationSize'));
        self::assertSame('pagination-secret', $container->getDefinition('ux_pagination.cursor_codec')->getArgument('$secret'));
        self::assertSame(
            '@UXPagination/theme/tailwind.html.twig',
            $container
                ->getDefinition('ux_pagination.renderer')
                ->getArgument('$defaultTheme'),
        );
    }

    public function testLoadWithCustomThemeName()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', ['TwigBundle' => TwigBundle::class]);
        $extension = $this->getExtension();

        $extension->load([
            ['theme' => '@App/pagination/my_theme.html.twig'],
        ], $container);

        self::assertSame(
            '@App/pagination/my_theme.html.twig',
            $container
                ->getDefinition('ux_pagination.renderer')
                ->getArgument('$defaultTheme'),
        );
    }

    public function testLoadWithApplicationThemePath()
    {
        $container = $this->createContainer();
        $container->setParameter(
            'kernel.bundles',
            ['TwigBundle' => TwigBundle::class],
        );

        $this->getExtension()->load([
            ['theme' => 'pagination/application.html.twig'],
        ], $container);

        self::assertSame(
            'pagination/application.html.twig',
            $container
                ->getDefinition('ux_pagination.renderer')
                ->getArgument('$defaultTheme'),
        );
    }

    public function testNamedPaginatorsInheritRootConfigurationAndOverrideSelectedValues()
    {
        $container = $this->createContainer();

        $this->getExtension()->load([[
            'items_per_page' => 30,
            'max_offset' => 200_000,
            'page_parameter' => 'p',
            'cursor_parameter' => 'after',
            'navigation' => ['mode' => 'fixed', 'size' => 8],
            'paginators' => [
                'blog' => [
                    'items_per_page' => 12,
                    'navigation' => ['size' => 7],
                ],
            ],
        ]], $container);

        $paginator = $container->getDefinition('ux_pagination.paginator.blog');
        self::assertFalse($paginator->isPublic());
        self::assertSame(12, $paginator->getArgument('$defaultPerPage'));
        self::assertSame(200_000, $paginator->getArgument('$defaultMaxOffset'));
        self::assertSame('p', $paginator->getArgument('$defaultPageParam'));
        self::assertSame('after', $paginator->getArgument('$defaultCursorParam'));
        self::assertSame(NavigationMode::Fixed, $paginator->getArgument('$defaultNavigationMode'));
        self::assertSame(7, $paginator->getArgument('$defaultNavigationSize'));
        self::assertSame('ux_pagination.paginator.blog', (string) $container->getAlias(PaginatorInterface::class.' $blogPaginator'));
        self::assertSame(PaginatorInterface::class.' $blogPaginator', (string) $container->getAlias('.'.PaginatorInterface::class.' $blog'));
    }

    public function testNamedPaginatorInjectionSupportsEveryPublicEntryPoint()
    {
        $container = $this->createContainer();
        $bundle = new UXPaginationBundle();
        $bundle->build($container);
        $bundle->getContainerExtension()->load([[
            'paginators' => [
                'blog' => [
                    'items_per_page' => 2,
                ],
            ],
        ]], $container);
        $container->register(NamedPaginatorConsumer::class)
            ->setAutowired(true)
            ->setPublic(true);
        $container->compile();

        /** @var NamedPaginatorConsumer $consumer */
        $consumer = $container->get(NamedPaginatorConsumer::class);
        $paginator = $consumer->blogPaginator;

        $simple = $paginator->paginate(range(1, 5));
        self::assertSame([1, 2], $simple->getItems());

        $numbered = $paginator->query(range(1, 5))->paginate();
        self::assertSame([1, 2], $numbered->getItems());

        $items = range(1, 5);
        $callbacks = $paginator
            ->fromCallbacks(
                static fn (int $offset, int $limit): array => \array_slice($items, $offset, $limit),
                static fn (): int => \count($items),
            )
            ->paginate();
        self::assertSame([1, 2], $callbacks->getItems());

        $cursor = $paginator
            ->cursor(array_map(static fn (int $id): array => ['id' => $id], $items))
            ->orderBy('id', 'ASC')
            ->context('blog')
            ->paginate();
        self::assertSame([1, 2], array_column($cursor->getItems(), 'id'));
    }

    public function testAdapterIteratorUsesTheSymfonyDefaultPriorityConvention()
    {
        $container = $this->createContainer();

        $this->getExtension()->load([], $container);

        $adapters = $container->getDefinition('ux_pagination.paginator')->getArgument('$adapters');
        self::assertInstanceOf(TaggedIteratorArgument::class, $adapters);
        self::assertSame('getDefaultPriority', $adapters->getDefaultPriorityMethod());
    }

    public function testAdapterDefaultPriorityMethodControlsResolutionOrder()
    {
        $container = $this->createContainerWithAdapters([
            'app.adapter.explicit' => [TaggedTestPaginationAdapter::class, ['explicit'], 10],
            'app.adapter.static' => [DefaultPriorityTestPaginationAdapter::class, [], null],
        ]);

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);

        self::assertSame(['static'], $paginator->paginate(new PriorityTestSource())->getItems());
    }

    public function testExplicitAdapterTagPriorityTakesPrecedence()
    {
        $container = $this->createContainerWithAdapters([
            'app.adapter.static' => [DefaultPriorityTestPaginationAdapter::class, [], null],
            'app.adapter.explicit' => [TaggedTestPaginationAdapter::class, ['explicit'], 100],
        ]);

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);

        self::assertSame(['explicit'], $paginator->paginate(new PriorityTestSource())->getItems());
    }

    public function testEqualAdapterPrioritiesKeepRegistrationOrder()
    {
        $container = $this->createContainerWithAdapters([
            'app.adapter.first' => [TaggedTestPaginationAdapter::class, ['first'], 10],
            'app.adapter.second' => [TaggedTestPaginationAdapter::class, ['second'], 10],
        ]);

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);

        self::assertSame(['first'], $paginator->paginate(new PriorityTestSource())->getItems());
    }

    public function testGetAlias()
    {
        $extension = $this->getExtension();

        self::assertSame('ux_pagination', $extension->getAlias());
    }

    public function testLoadRegistersDoctrineOrmAdapter()
    {
        if (!class_exists(\Doctrine\ORM\QueryBuilder::class)) {
            self::markTestSkipped('Doctrine ORM is not installed.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('ux_pagination.adapter.doctrine_orm'));
    }

    public function testLoadRegistersDoctrineDbalAdapter()
    {
        if (!class_exists(\Doctrine\DBAL\Query\QueryBuilder::class)) {
            self::markTestSkipped('Doctrine DBAL is not installed.');
        }

        $container = $this->createContainer();

        $this->getExtension()->load([], $container);

        self::assertTrue($container->hasDefinition('ux_pagination.adapter.doctrine_dbal'));
    }

    public function testTwigServicesAreNotRegisteredWhenTwigBundleIsInstalledButInactive()
    {
        self::assertTrue(class_exists(TwigBundle::class));

        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', []);

        $this->getExtension()->load([], $container);

        self::assertFalse($container->hasDefinition('ux_pagination.twig.extension'));
        self::assertFalse($container->hasDefinition('ux_pagination.renderer'));
    }

    public function testTwigServicesAreRegisteredWhenTwigBundleIsActive()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', ['TwigBundle' => TwigBundle::class]);

        $this->getExtension()->load([], $container);

        self::assertTrue($container->hasDefinition('ux_pagination.twig.extension'));
        self::assertTrue($container->getDefinition('ux_pagination.twig.extension')->isAutoconfigured());
        self::assertTrue($container->hasDefinition('ux_pagination.renderer'));
    }

    public function testCustomAdaptersAreAutoconfigured()
    {
        $container = $this->createContainer();
        new UXPaginationBundle()->build($container);
        $container->register('app.pagination_adapter', TestPaginationAdapter::class)
            ->setAutoconfigured(true)
            ->setPublic(true);
        $container->compile();

        self::assertSame(['ux_pagination.adapter' => [[]]], $container->getDefinition('app.pagination_adapter')->getTags());
    }

    private function getExtension(): ExtensionInterface
    {
        $bundle = new UXPaginationBundle();

        return $bundle->getContainerExtension();
    }

    public function testMissingKernelSecretFallsBackToAnEmptyCursorSecret()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', true);

        $this->getExtension()->load([], $container);

        self::assertSame('', $container->getDefinition('ux_pagination.cursor_codec')->getArgument('$secret'));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.secret', 'kernel-secret');

        return $container;
    }

    /**
     * @param array<string, array{class-string<PaginationAdapterInterface>, list<mixed>, int|null}> $adapters
     */
    private function createContainerWithAdapters(array $adapters): ContainerBuilder
    {
        $container = $this->createContainer();
        new UXPaginationBundle()->build($container);
        $this->getExtension()->load([], $container);

        foreach ($adapters as $id => [$class, $arguments, $priority]) {
            $attributes = null === $priority ? [] : ['priority' => $priority];
            $container->register($id, $class)
                ->setArguments($arguments)
                ->addTag('ux_pagination.adapter', $attributes);
        }

        $container->getAlias(PaginatorInterface::class)->setPublic(true);
        $container->compile();

        return $container;
    }
}

final class TestPaginationAdapter implements OffsetAdapterInterface
{
    public function supports(mixed $source): bool
    {
        return false;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        return [];
    }

    public function count(mixed $source): int
    {
        return 0;
    }
}

final class PriorityTestSource
{
}

final class NamedPaginatorConsumer
{
    public function __construct(
        public readonly PaginatorInterface $blogPaginator,
    ) {
    }
}

final class TaggedTestPaginationAdapter implements OffsetAdapterInterface
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function supports(mixed $source): bool
    {
        return $source instanceof PriorityTestSource;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        return [$this->value];
    }

    public function count(mixed $source): int
    {
        return 1;
    }
}

final class DefaultPriorityTestPaginationAdapter implements OffsetAdapterInterface
{
    public static function getDefaultPriority(): int
    {
        return 50;
    }

    public function supports(mixed $source): bool
    {
        return $source instanceof PriorityTestSource;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        return ['static'];
    }

    public function count(mixed $source): int
    {
        return 1;
    }
}
