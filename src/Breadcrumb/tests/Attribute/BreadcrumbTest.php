<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Exception\InvalidArgumentException;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductViewController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;

#[CoversClass(Breadcrumb::class)]
final class BreadcrumbTest extends TestCase
{
    public function testOnlyTheLabelIsRequired(): void
    {
        $crumb = new Breadcrumb('product.index.breadcrumb');

        self::assertSame('product.index.breadcrumb', $crumb->label);
        self::assertNull($crumb->route);
        self::assertSame([], $crumb->parameters);
        self::assertNull($crumb->translationDomain);
        self::assertSame([], $crumb->translationParameters);
        self::assertSame([], $crumb->extra);
        self::assertNull($crumb->parent);
    }

    public function testNamedArgumentsLandOnTheMatchingProperties(): void
    {
        $crumb = new Breadcrumb(
            label: 'product.view.breadcrumb',
            route: RouteName::ProductView->value,
            parameters: ['slug', 'page' => 2],
            translationDomain: 'admin',
            translationParameters: ['name' => 'Blue sneakers'],
            extra: ['icon' => 'tabler:package'],
            parent: 'product_index',
        );

        self::assertSame('product.view.breadcrumb', $crumb->label);
        self::assertSame(RouteName::ProductView->value, $crumb->route);
        self::assertSame(['slug', 'page' => 2], $crumb->parameters);
        self::assertSame('admin', $crumb->translationDomain);
        self::assertSame(['name' => 'Blue sneakers'], $crumb->translationParameters);
        self::assertSame(['icon' => 'tabler:package'], $crumb->extra);
        self::assertSame('product_index', $crumb->parent);
    }

    public function testParametersAcceptAnExpression(): void
    {
        $expression = new Expression('product.state');

        self::assertSame(['state' => $expression], new Breadcrumb('label', parameters: ['state' => $expression])->parameters);
        self::assertSame(['name' => $expression], new Breadcrumb('label', translationParameters: ['name' => $expression])->translationParameters);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidInheritedParameters(): iterable
    {
        yield 'empty name' => [''];
        yield 'integer' => [1];
        yield 'expression' => [new Expression('product.slug')];
    }

    #[DataProvider('provideInvalidInheritedParameters')]
    public function testAnUnkeyedParameterMustBeARouteParameterName(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb('label', parameters: [$value]);
    }

    public function testTranslationParametersMustBeKeyed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb('label', translationParameters: ['name']);
    }

    public function testTheLabelAcceptsAnExpression(): void
    {
        $label = new Expression('product.name');

        self::assertSame($label, new Breadcrumb(label: $label)->label);
        self::assertSame($label, new Breadcrumb(label: $label, translationDomain: false)->label);
    }

    public function testAnExpressionLabelTakesNoTranslationDomain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb(label: new Expression('product.name'), translationDomain: 'admin');
    }

    public function testAnExpressionLabelTakesNoTranslationParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb(label: new Expression('product.name'), translationParameters: ['name' => 'Blue sneakers']);
    }

    public function testAPatternLabelMayOptOutOfTranslation(): void
    {
        self::assertSame('{name:product}', new Breadcrumb(label: '{name:product}', translationDomain: false)->label);
    }

    public function testAPatternLabelTakesNoTranslationDomain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb(label: '{name:product}', translationDomain: 'admin');
    }

    public function testAPatternLabelTakesNoTranslationParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Breadcrumb(label: 'Edit {name:product}', translationParameters: ['name' => 'Blue sneakers']);
    }

    public function testTranslationDomainAcceptsTheThreeStates(): void
    {
        self::assertNull(new Breadcrumb('label')->translationDomain);
        self::assertSame('admin', new Breadcrumb('label', translationDomain: 'admin')->translationDomain);
        self::assertFalse(new Breadcrumb('label', translationDomain: false)->translationDomain);
    }

    public function testRouteIsARouteName(): void
    {
        self::assertSame('product_index', new Breadcrumb('label', route: 'product_index')->route);
        self::assertSame('product_index', new Breadcrumb('label', route: RouteName::ProductIndex->value)->route);
    }

    public function testParentIsAClassARouteNameOrAnAction(): void
    {
        self::assertSame(ProductViewController::class, new Breadcrumb('label', parent: ProductViewController::class)->parent);
        self::assertSame('product_index', new Breadcrumb('label', parent: 'product_index')->parent);
        self::assertSame([ProductViewController::class, 'view'], new Breadcrumb('label', parent: [ProductViewController::class, 'view'])->parent);
    }

    public function testTheAttributeIsRepeatableOnClassesAndMethods(): void
    {
        $attribute = new \ReflectionClass(Breadcrumb::class)->getAttributes(\Attribute::class)[0]->newInstance();

        self::assertSame(
            \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE,
            $attribute->flags,
        );
    }
}
