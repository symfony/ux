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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Pagination\Cursor\CursorCodecInterface;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\PaginatorInterface;
use Symfony\UX\Pagination\UXPaginationBundle;

#[CoversNothing]
final class CursorSecretLifecycleTest extends TestCase
{
    public function testCursorCodecUsesAnInterfaceBasedLazyProxy(): void
    {
        $container = $this->loadContainer(kernelSecret: null);
        $definition = $container->getDefinition('ux_pagination.cursor_codec');

        self::assertTrue($definition->isLazy());
        self::assertSame(
            [['interface' => CursorCodecInterface::class]],
            $definition->getTag('proxy'),
        );
    }

    public function testOffsetPaginationWorksWithoutAnySigningSecret(): void
    {
        $container = $this->loadContainer(kernelSecret: null);
        $container->compile();

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);
        $pagination = $paginator->paginate(range(1, 5), page: 2, perPage: 2);

        self::assertSame([3, 4], $pagination->getItems());
    }

    public function testMissingSecretFailsOnlyWhenCursorSigningIsNeeded(): void
    {
        $container = $this->loadContainer(kernelSecret: null);
        $container->compile();

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);
        $pagination = $paginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non-empty "ux_pagination.cursor.secret" or "kernel.secret"');

        $pagination->getItems();
    }

    public function testMissingSecretIsNotReportedAsAnInvalidClientCursor(): void
    {
        $container = $this->loadContainer(kernelSecret: null);
        $container->compile();

        /** @var PaginatorInterface $paginator */
        $paginator = $container->get(PaginatorInterface::class);
        $builder = $paginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->cursor('opaque-client-value')
            ->perPage(2)
            ->context('events');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non-empty "ux_pagination.cursor.secret" or "kernel.secret"');

        $builder->paginate();
    }

    public function testKernelSecretIsUsedAsTheDefaultCursorSecret(): void
    {
        $firstContainer = $this->loadContainer(kernelSecret: 'shared-kernel-secret');
        $firstContainer->compile();

        /** @var PaginatorInterface $firstPaginator */
        $firstPaginator = $firstContainer->get(PaginatorInterface::class);
        $firstPage = $firstPaginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();
        $cursor = $firstPage->getNextCursor();

        self::assertNotNull($cursor);

        $secondContainer = $this->loadContainer(kernelSecret: 'shared-kernel-secret');
        $secondContainer->compile();

        /** @var PaginatorInterface $secondPaginator */
        $secondPaginator = $secondContainer->get(PaginatorInterface::class);
        $secondPage = $secondPaginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->cursor($cursor)
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([3, 4], array_column($secondPage->getItems(), 'id'));
    }

    public function testDedicatedCursorSecretOverridesKernelSecret(): void
    {
        $firstContainer = $this->loadContainer(
            kernelSecret: 'first-kernel-secret',
            cursorSecret: 'dedicated-cursor-secret',
        );
        $firstContainer->compile();

        /** @var PaginatorInterface $firstPaginator */
        $firstPaginator = $firstContainer->get(PaginatorInterface::class);
        $cursor = $firstPaginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate()
            ->getNextCursor();

        self::assertNotNull($cursor);

        $secondContainer = $this->loadContainer(
            kernelSecret: 'another-kernel-secret',
            cursorSecret: 'dedicated-cursor-secret',
        );
        $secondContainer->compile();

        /** @var PaginatorInterface $secondPaginator */
        $secondPaginator = $secondContainer->get(PaginatorInterface::class);
        $secondPage = $secondPaginator
            ->cursor($this->cursorSource())
            ->orderBy('id', 'ASC')
            ->cursor($cursor)
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([3, 4], array_column($secondPage->getItems(), 'id'));
    }

    private function loadContainer(?string $kernelSecret, ?string $cursorSecret = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.bundles', []);
        if (null !== $kernelSecret) {
            $container->setParameter('kernel.secret', $kernelSecret);
        }

        $bundle = new UXPaginationBundle();
        $bundle->build($container);
        $config = null === $cursorSecret ? [] : [['cursor' => ['secret' => $cursorSecret]]];
        $bundle->getContainerExtension()->load($config, $container);

        $container->getAlias(PaginatorInterface::class)->setPublic(true);

        return $container;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function cursorSource(): array
    {
        return array_map(
            static fn (int $id): array => ['id' => $id, 'name' => 'Event '.$id],
            range(1, 6),
        );
    }
}
