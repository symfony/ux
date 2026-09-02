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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Pagination\Navigation\NavigationMode;
use Symfony\UX\Pagination\UXPaginationBundle;

#[CoversClass(UXPaginationBundle::class)]
final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $config = $this->processConfiguration([]);

        self::assertSame(20, $config['items_per_page']);
        self::assertSame(100_000, $config['max_offset']);
        self::assertSame('page', $config['page_parameter']);
        self::assertSame('cursor', $config['cursor_parameter']);
        self::assertSame(['mode' => NavigationMode::Sliding, 'size' => 5], $config['navigation']);
        self::assertSame(
            '@UXPagination/theme/default.html.twig',
            $config['theme'],
        );
        self::assertSame(['secret' => '%kernel.secret%'], $config['cursor']);
        self::assertSame([], $config['paginators']);
    }

    public function testCustomConfiguration()
    {
        $config = $this->processConfiguration([
            'items_per_page' => 50,
            'max_offset' => 250_000,
            'page_parameter' => 'p',
            'cursor_parameter' => 'after',
            'navigation' => [
                'mode' => 'fixed',
                'size' => 9,
            ],
            'theme' => '@UXPagination/theme/bootstrap.html.twig',
            'cursor' => ['secret' => 'pagination-secret'],
            'paginators' => [
                'blog' => [
                    'items_per_page' => 12,
                    'navigation' => ['size' => 7],
                ],
            ],
        ]);

        self::assertSame(50, $config['items_per_page']);
        self::assertSame(250_000, $config['max_offset']);
        self::assertSame('p', $config['page_parameter']);
        self::assertSame('after', $config['cursor_parameter']);
        self::assertSame(['mode' => NavigationMode::Fixed, 'size' => 9], $config['navigation']);
        self::assertSame(
            '@UXPagination/theme/bootstrap.html.twig',
            $config['theme'],
        );
        self::assertSame(['secret' => 'pagination-secret'], $config['cursor']);
        self::assertSame([
            'blog' => [
                'items_per_page' => 12,
                'navigation' => ['size' => 7],
            ],
        ], $config['paginators']);
    }

    public function testItemsPerPageMinimum()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['items_per_page' => 0]);
    }

    public function testItemsPerPageMustBePositive()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['items_per_page' => -5]);
    }

    public function testMaximumOffsetCannotBeNegative()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['max_offset' => -1]);
    }

    #[DataProvider('emptyStringOptions')]
    public function testStringOptionsCannotBeEmpty(array $config)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration($config);
    }

    public function testThemeMustBeASingleValue()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration([
            'theme' => [
                '@UXPagination/theme/default.html.twig',
                '@UXPagination/theme/bootstrap.html.twig',
            ],
        ]);
    }

    public function testTemplateIsNotAConfigurationOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "template"');

        $this->processConfiguration([
            'template' => '@UXPagination/theme/bootstrap.html.twig',
        ]);
    }

    public function testThemeIsNotNestedUnderTwig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "twig"');

        $this->processConfiguration([
            'twig' => [
                'theme' => '@UXPagination/theme/bootstrap.html.twig',
            ],
        ]);
    }

    public static function emptyStringOptions(): iterable
    {
        yield 'Twig theme' => [['theme' => '']];
        yield 'blank Twig theme' => [['theme' => '   ']];
        yield 'query parameter' => [['page_parameter' => '']];
        yield 'blank query parameter' => [['page_parameter' => '   ']];
        yield 'cursor parameter' => [['cursor_parameter' => '']];
        yield 'named paginator query parameter' => [['paginators' => ['blog' => ['page_parameter' => '']]]];
        yield 'blank named paginator query parameter' => [['paginators' => ['blog' => ['page_parameter' => '   ']]]];
        yield 'named paginator cursor parameter' => [['paginators' => ['blog' => ['cursor_parameter' => '']]]];
    }

    #[DataProvider('nonStringOptions')]
    public function testStringOptionsRejectNonStringScalars(array $config)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration($config);
    }

    public static function nonStringOptions(): iterable
    {
        yield 'Twig theme' => [['theme' => 123]];
        yield 'query parameter' => [['page_parameter' => false]];
        yield 'cursor parameter' => [['cursor_parameter' => 123]];
        yield 'named query parameter' => [['paginators' => ['blog' => ['page_parameter' => 123]]]];
        yield 'named cursor parameter' => [['paginators' => ['blog' => ['cursor_parameter' => false]]]];
    }

    public function testItemsPerPageRejectsLookaheadOverflow()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['items_per_page' => \PHP_INT_MAX]);
    }

    public function testNamedItemsPerPageRejectsLookaheadOverflow()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration([
            'paginators' => [
                'blog' => ['items_per_page' => \PHP_INT_MAX],
            ],
        ]);
    }

    public function testNavigationModeMustBeSupported()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['navigation' => ['mode' => 'unknown']]);
    }

    public function testNamedPaginatorValuesAreValidated()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['paginators' => ['blog' => ['navigation' => ['size' => 0]]]]);
    }

    public function testPaginatorNameMustBeAValidAutowiringTarget()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid paginator name "1blog"');

        $this->processConfiguration(['paginators' => ['1blog' => []]]);
    }

    public function testPaginatorNamesMustNotResolveToTheSameAutowiringTarget()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Paginator names "blog-posts" and "blog_posts" resolve to the same autowiring target "blogPosts".');

        $this->processConfiguration([
            'paginators' => [
                'blog-posts' => [],
                'blog_posts' => [],
            ],
        ]);
    }

    public function testTreeBuilderName()
    {
        $bundle = new UXPaginationBundle();
        $extension = $bundle->getContainerExtension();
        $container = new ContainerBuilder();

        $configuration = $extension->getConfiguration([], $container);

        self::assertSame('ux_pagination', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    private function processConfiguration(array $input): array
    {
        $bundle = new UXPaginationBundle();
        $extension = $bundle->getContainerExtension();
        $container = new ContainerBuilder();

        $configuration = $extension->getConfiguration([], $container);
        $processor = new Processor();

        return $processor->processConfiguration($configuration, ['ux_pagination' => $input]);
    }
}
