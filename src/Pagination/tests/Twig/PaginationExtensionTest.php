<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\Twig\PaginationExtension;
use Symfony\UX\Pagination\Twig\PaginationRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(PaginationExtension::class)]
final class PaginationExtensionTest extends TestCase
{
    public function testRenderPaginationUsesDefaultTheme()
    {
        $pagination = $this->createPagination();

        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme:{{ pagination.info }}',
        ])));

        self::assertSame('default-theme:'.$pagination->getInfo(), $extension->renderPagination($pagination));
    }

    public function testRenderPaginationPassesExplicitArgumentsToTheme()
    {
        $pagination = $this->createPagination();

        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/bootstrap.html.twig' => 'bootstrap-theme:{{ show_info ? "info-on" : "info-off" }}:{{ attributes.class|default("no-class") }}',
        ])));

        $result = $extension->renderPagination(
            $pagination,
            ['class' => 'my-nav'],
            theme: '@UXPagination/theme/bootstrap.html.twig',
            showInfo: false,
        );

        self::assertSame('bootstrap-theme:info-off:my-nav', $result);
    }

    public function testRenderDispatchesToRenderPagination()
    {
        $pagination = $this->createPagination();

        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/tailwind.html.twig' => "tailwind-theme:{{ attributes.theme is defined ? 'leaked-theme' : 'no-theme' }}",
        ])));

        $result = $extension->render([
            'pagination' => $pagination,
            'theme' => '@UXPagination/theme/tailwind.html.twig',
        ]);

        self::assertSame('tailwind-theme:no-theme', $result);
    }

    public function testRenderRequiresPaginationArgument()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "pagination" argument must be a PaginationInterface instance.');

        $extension->render();
    }

    public function testRenderRejectsInvalidTheme()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('theme');

        $extension->render(['pagination' => $this->createPagination(), 'theme' => false]);
    }

    public function testRenderRejectsEmptyTheme()
    {
        $extension = new PaginationExtension(
            new PaginationRenderer($this->createTwig()),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "theme" argument must be a non-empty string or null.',
        );

        $extension->render([
            'pagination' => $this->createPagination(),
            'theme' => '',
        ]);
    }

    public function testRenderRejectsInvalidShowInfo()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('showInfo');

        $extension->render(['pagination' => $this->createPagination(), 'showInfo' => 'yes']);
    }

    public function testRenderAcceptsAttributeGroups()
    {
        $pagination = $this->createPagination();
        $linkAttributes = static fn (array $link): array => ['data-page' => $link['page']];

        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => "{{ attributes.id }}:{{ attributes.class }}:{{ navigation_attributes.class }}:{{ link_attributes.pages[2]['data-page'] }}",
        ])));

        $result = $extension->render([
            'pagination' => $pagination,
            'attributes' => ['id' => 'product-pages'],
            'class' => 'product-pagination',
            'navigationAttributes' => ['class' => 'controls'],
            'linkAttributes' => $linkAttributes,
        ]);

        self::assertSame('product-pages:product-pagination:controls:2', $result);
    }

    public function testRenderRejectsInvalidLinkAttributes()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('linkAttributes');

        $extension->render(['pagination' => $this->createPagination(), 'linkAttributes' => 'unsafe']);
    }

    public function testRenderRejectsInvalidRootAttributes()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"attributes" argument must be an array');

        $extension->render(['pagination' => $this->createPagination(), 'attributes' => 'invalid']);
    }

    public function testRenderRejectsInvalidNavigationAttributes()
    {
        $extension = new PaginationExtension(new PaginationRenderer($this->createTwig()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"navigationAttributes" argument must be an array');

        $extension->render([
            'pagination' => $this->createPagination(),
            'navigationAttributes' => 'invalid',
        ]);
    }

    /**
     * @param array<string, string> $templates
     */
    private function createTwig(array $templates = []): Environment
    {
        return new Environment(new ArrayLoader($templates), ['strict_variables' => true]);
    }

    /**
     * @param array<int, mixed> $source
     */
    private function createPagination(array $source = [], int $page = 1, int $perPage = 10): Pagination
    {
        if (empty($source)) {
            $source = range(1, 100);
        }

        return new Pagination(
            source: $source,
            adapter: new ArrayPaginationAdapter(),
            currentPage: $page,
            perPage: $perPage,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
    }
}
