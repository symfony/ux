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
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;
use Symfony\UX\Pagination\NumberedPaginationInterface;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationInterface;
use Symfony\UX\Pagination\Twig\PaginationRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(PaginationRenderer::class)]
final class PaginationRendererTest extends TestCase
{
    public function testRenderUsesDefaultTheme()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme:{{ pagination.info }}',
        ]));

        $result = $renderer->renderPagination($pagination);

        self::assertSame('default-theme:'.$pagination->getInfo(), $result);
    }

    public function testRenderWithBootstrapTheme()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/bootstrap.html.twig' => 'bootstrap-theme:{{ pagination.info }}',
        ]));

        $result = $renderer->renderPagination(
            $pagination,
            theme: '@UXPagination/theme/bootstrap.html.twig',
        );

        self::assertSame('bootstrap-theme:'.$pagination->getInfo(), $result);
    }

    public function testRenderWithTailwindTheme()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/tailwind.html.twig' => 'tailwind-theme:{{ pagination.info }}',
        ]));

        $result = $renderer->renderPagination(
            $pagination,
            theme: '@UXPagination/theme/tailwind.html.twig',
        );

        self::assertSame('tailwind-theme:'.$pagination->getInfo(), $result);
    }

    public function testRenderWithCustomTheme()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            'custom/pagination.html.twig' => 'custom-theme:{{ pagination.info }}',
        ]));

        $result = $renderer->renderPagination($pagination, theme: 'custom/pagination.html.twig');

        self::assertSame('custom-theme:'.$pagination->getInfo(), $result);
    }

    public function testRootLevelTwigPathIsPreserved()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            'pagination.html.twig' => 'root-theme:{{ pagination.info }}',
        ]));

        $result = $renderer->renderPagination(
            $pagination,
            theme: 'pagination.html.twig',
        );

        self::assertSame('root-theme:'.$pagination->getInfo(), $result);
    }

    public function testExplicitThemeMustNotBeEmpty()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "theme" argument must be a non-empty string or null.',
        );

        $renderer->renderPagination($this->createPagination(), theme: '  ');
    }

    public function testConfiguredDefaultThemeMustNotBeEmpty()
    {
        $renderer = new PaginationRenderer(
            $this->createTwig(),
            '',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "theme" argument must be a non-empty string or null.',
        );

        $renderer->renderPagination($this->createPagination());
    }

    public function testRenderPassesOptionsAsThemeVariables()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => '{{ show_info ? "info-on" : "info-off" }}:{{ attributes.class|default("no-class") }}:{{ showInfo is defined ? "camel-case" : "snake-case" }}',
        ]));

        $result = $renderer->renderPagination($pagination, ['class' => 'my-nav'], showInfo: false);

        self::assertSame('info-off:my-nav:snake-case', $result);
    }

    public function testEmptyLinkAttributesDoNotTraverseNumberedLinks()
    {
        $pagination = $this->createMock(NumberedPaginationInterface::class);
        $pagination->expects(self::never())->method('getPages');

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => '{{ link_attributes.previous|length }}:{{ link_attributes.pages|length }}:{{ link_attributes.next|length }}',
        ]));

        self::assertSame('0:0:0', $renderer->renderPagination($pagination));
    }

    public function testRenderWithConfiguredDefaultTheme()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer(
            $this->createTwig([
                '@UXPagination/theme/default.html.twig' => 'default-theme',
                '@UXPagination/theme/bootstrap.html.twig' => 'bootstrap-theme',
            ]),
            '@UXPagination/theme/bootstrap.html.twig',
        );

        $result = $renderer->renderPagination($pagination);

        self::assertSame('bootstrap-theme', $result);
    }

    public function testExplicitThemeOverridesConfiguredDefault()
    {
        $pagination = $this->createPagination();

        $renderer = new PaginationRenderer(
            $this->createTwig([
                '@UXPagination/theme/bootstrap.html.twig' => 'bootstrap-theme',
                '@UXPagination/theme/tailwind.html.twig' => 'tailwind-theme',
            ]),
            '@UXPagination/theme/bootstrap.html.twig',
        );

        $result = $renderer->renderPagination(
            $pagination,
            theme: '@UXPagination/theme/tailwind.html.twig',
        );

        self::assertSame('tailwind-theme', $result);
    }

    public function testRenderCursorPaginationUsesDefaultTheme()
    {
        $pagination = $this->createCursorPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme:{{ numbered ? "numbered" : "plain" }}',
        ]));

        $result = $renderer->renderPagination($pagination);

        self::assertSame('default-theme:plain', $result);
    }

    public function testBootstrapThemeSupportsCursorPagination()
    {
        $pagination = $this->createCursorPagination();

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/bootstrap.html.twig' => 'bootstrap-theme:{{ numbered ? "numbered" : "plain" }}',
        ]));

        self::assertSame(
            'bootstrap-theme:plain',
            $renderer->renderPagination(
                $pagination,
                theme: '@UXPagination/theme/bootstrap.html.twig',
            ),
        );
    }

    public function testBuiltInThemeSupportsThirdPartyPaginationContract()
    {
        $pagination = $this->createStub(PaginationInterface::class);

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => 'default-theme',
            '@UXPagination/theme/tailwind.html.twig' => 'tailwind-theme:{{ numbered ? "numbered" : "plain" }}',
        ]));

        self::assertSame(
            'tailwind-theme:plain',
            $renderer->renderPagination(
                $pagination,
                theme: '@UXPagination/theme/tailwind.html.twig',
            ),
        );
    }

    public function testCursorPaginationUsesConfiguredCustomTemplate()
    {
        $pagination = $this->createCursorPagination();

        $renderer = new PaginationRenderer(
            $this->createTwig([
                '@UXPagination/theme/default.html.twig' => 'default-theme',
                'app/pagination.html.twig' => 'custom-cursor-theme',
            ]),
            'app/pagination.html.twig',
        );

        self::assertSame('custom-cursor-theme', $renderer->renderPagination($pagination));
    }

    public function testThemeRendersLikeAnOrdinaryTwigTemplate()
    {
        $renderer = new PaginationRenderer($this->createTwig([
            'block.html.twig' => '{% block pagination %}probe-block{% endblock %}{% block leak %}LEAK{% endblock %}',
            'standalone.html.twig' => 'standalone-body',
        ]));
        $pagination = $this->createPagination();

        self::assertSame('probe-blockLEAK', $renderer->renderPagination($pagination, theme: 'block.html.twig'));
        self::assertSame('standalone-body', $renderer->renderPagination($pagination, theme: 'standalone.html.twig'));
    }

    public function testRenderResolvesLinkAttributeClosureWithNavigationContext()
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => "{{ link_attributes.previous['data-page'] }}:{{ link_attributes.pages[3]['data-page'] }}:{{ link_attributes.next['data-page'] }}",
        ]));
        $html = $renderer->renderPagination(
            $pagination,
            linkAttributes: static fn (array $link): array => ['data-page' => $link['page']],
        );

        self::assertSame('1:3:3', $html);
    }

    public function testRenderRejectsInvalidAttributeName()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid attribute name');

        $renderer->renderPagination($this->createPagination(), ['onload x' => 'alert(1)']);
    }

    public function testRenderRejectsNonScalarAttributeValue()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not scalar or Stringable');

        $renderer->renderPagination($this->createPagination(), ['data-context' => []]);
    }

    public function testRenderRejectsNonStringClass()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "class" value');

        $renderer->renderPagination($this->createPagination(), ['CLASS' => true]);
    }

    public function testRenderRejectsHrefOverrideFromLinkAttributes()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot override the "href"');

        $renderer->renderPagination($this->createPagination(), linkAttributes: ['href' => 'javascript:alert(1)']);
    }

    public function testCursorLinkContextContainsTheOpaqueCursor()
    {
        $renderer = new PaginationRenderer($this->createTwig([
            '@UXPagination/theme/default.html.twig' => "{{ link_attributes.next['data-cursor'] is null ? 'missing-cursor' : 'opaque-cursor' }}",
        ]));
        $html = $renderer->renderPagination(
            $this->createCursorPagination(),
            linkAttributes: static fn (array $link): array => ['data-cursor' => $link['cursor']],
        );

        self::assertSame('opaque-cursor', $html);
    }

    public function testLinkContextRejectsAnInconsistentPaginationResult()
    {
        $pagination = $this->createStub(PaginationInterface::class);
        $pagination->method('hasNext')->willReturn(true);
        $pagination->method('getNextUrl')->willReturn(null);
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reports a "next" link without a URL');

        $renderer->renderPagination($pagination, linkAttributes: static fn (array $link): array => []);
    }

    public function testRenderRejectsInvalidClosureReturnValue()
    {
        $renderer = new PaginationRenderer($this->createTwig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('callable must return an array');

        $renderer->renderPagination(
            $this->createPagination(),
            linkAttributes: static fn (): string => 'invalid',
        );
    }

    /**
     * @param array<string, string> $templates
     */
    private function createTwig(array $templates = []): Environment
    {
        return new Environment(new ArrayLoader($templates), ['strict_variables' => true]);
    }

    private function createPagination(): Pagination
    {
        return new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
    }

    private function createCursorPagination(): CursorPagination
    {
        $adapter = $this->createStub(CursorAdapterInterface::class);
        $adapter->method('sliceWithCursor')->willReturn(new \Symfony\UX\Pagination\Cursor\CursorSlice(
            range(1, 10),
            new \Symfony\UX\Pagination\Cursor\CursorBoundary([10]),
            null,
            true,
        ));

        return new CursorPagination(
            source: range(1, 100),
            adapter: $adapter,
            cursor: null,
            perPage: 10,
            order: \Symfony\UX\Pagination\Cursor\CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
    }
}
