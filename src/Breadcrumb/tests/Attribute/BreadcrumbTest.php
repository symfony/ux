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
use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
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
        self::assertSame([], $crumb->inheritedParameters);
        self::assertSame([], $crumb->computedParameters);
        self::assertNull($crumb->translationDomain);
        self::assertSame([], $crumb->translationParameters);
        self::assertSame([], $crumb->extra);
    }

    public function testNamedArgumentsLandOnTheMatchingProperties(): void
    {
        $crumb = new Breadcrumb(
            label: 'product.view.breadcrumb',
            route: RouteName::ProductView->value,
            parameters: ['page' => 2],
            inheritedParameters: ['slug'],
            computedParameters: ['state' => 'product.state'],
            translationDomain: 'admin',
            translationParameters: ['name' => 'product.name'],
            extra: ['icon' => 'tabler:package'],
        );

        self::assertSame('product.view.breadcrumb', $crumb->label);
        self::assertSame(RouteName::ProductView->value, $crumb->route);
        self::assertSame(['page' => 2], $crumb->parameters);
        self::assertSame(['slug'], $crumb->inheritedParameters);
        self::assertSame(['state' => 'product.state'], $crumb->computedParameters);
        self::assertSame('admin', $crumb->translationDomain);
        self::assertSame(['name' => 'product.name'], $crumb->translationParameters);
        self::assertSame(['icon' => 'tabler:package'], $crumb->extra);
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

    public function testTheAttributeIsRepeatableOnClassesAndMethods(): void
    {
        $attribute = new \ReflectionClass(Breadcrumb::class)->getAttributes(\Attribute::class)[0]->newInstance();

        self::assertSame(
            \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE,
            $attribute->flags,
        );
    }
}
