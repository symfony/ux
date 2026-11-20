<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\Exception\LogicException;

#[CoversClass(BreadcrumbTrail::class)]
final class BreadcrumbTrailTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $trail = new BreadcrumbTrail();

        self::assertTrue($trail->isEmpty());
        self::assertSame([], $trail->all());
        self::assertSame('', $trail->route);
        self::assertSame([], $trail->routeParameters);
        self::assertSame([], $trail->context);
    }

    public function testExposesTheContextItWasBuiltWith(): void
    {
        $trail = new BreadcrumbTrail('product_view', ['slug' => 'blue-sneakers'], ['product' => 'entity']);

        self::assertSame('product_view', $trail->route);
        self::assertSame(['slug' => 'blue-sneakers'], $trail->routeParameters);
        self::assertSame(['product' => 'entity'], $trail->context);
    }

    public function testAppendPreservesOrderAcrossSingleAndVariadicCalls(): void
    {
        $trail = new BreadcrumbTrail();
        $first = new Breadcrumb('first');
        $second = new Breadcrumb('second');
        $third = new Breadcrumb('third');

        $trail->append($first);
        $trail->append($second, $third);

        self::assertFalse($trail->isEmpty());
        self::assertSame([$first, $second, $third], $trail->all());
    }

    public function testPrependPutsACrumbBeforeTheExistingOnes(): void
    {
        $leaf = new Breadcrumb('leaf');
        $root = new Breadcrumb('root');

        $trail = new BreadcrumbTrail();
        $trail->append($leaf);
        $trail->prepend($root);

        self::assertSame([$root, $leaf], $trail->all());
    }

    public function testPrependKeepsTheRelativeOrderOfThePrependedCrumbs(): void
    {
        $leaf = new Breadcrumb('leaf');
        $root = new Breadcrumb('root');
        $section = new Breadcrumb('section');

        $trail = new BreadcrumbTrail();
        $trail->append($leaf);
        $trail->prepend($root, $section);

        self::assertSame([$root, $section, $leaf], $trail->all());
    }

    public function testAClosureIsExpandedInPlaceWhenTheTrailIsRead(): void
    {
        $root = new Breadcrumb('root');
        $section = new Breadcrumb('section');
        $category = new Breadcrumb('category');
        $subcategory = new Breadcrumb('subcategory');
        $leaf = new Breadcrumb('leaf');
        $trail = new BreadcrumbTrail();
        $trail->append($category, static fn (): iterable => yield $subcategory, $leaf);
        $trail->prepend(static fn (): array => [$root, $section]);

        $crumbs = $trail->all();

        self::assertSame([$root, $section, $category, $subcategory, $leaf], $crumbs);
    }

    public function testAClosureIsNotCalledUntilTheCrumbsAreRead(): void
    {
        $calls = 0;
        $trail = new BreadcrumbTrail();
        $trail->append(static function () use (&$calls): array {
            ++$calls;

            return [];
        });

        $isEmpty = $trail->isEmpty();

        self::assertFalse($isEmpty);
        self::assertSame(0, $calls);
    }

    public function testAClosureIsCalledOnceAcrossReads(): void
    {
        $calls = 0;
        $crumb = new Breadcrumb('leaf');
        $trail = new BreadcrumbTrail();
        $trail->append(static function () use (&$calls, $crumb): array {
            ++$calls;

            return [$crumb];
        });

        $first = $trail->all();
        $second = $trail->all();

        self::assertSame([$crumb], $first);
        self::assertSame([$crumb], $second);
        self::assertSame(1, $calls);
    }

    public function testAClosureMayAddToTheTrailItIsAddedTo(): void
    {
        $root = new Breadcrumb('root');
        $category = new Breadcrumb('category');
        $subcategory = new Breadcrumb('subcategory');
        $leaf = new Breadcrumb('leaf');
        $trail = new BreadcrumbTrail();
        $trail->append($category, static function () use ($trail, $root, $leaf): array {
            $trail->prepend($root);
            $trail->append(static fn (): array => [$leaf]);

            return [];
        });
        $trail->append(static fn (): array => [$subcategory]);

        $first = $trail->all();
        $second = $trail->all();

        self::assertSame([$root, $category, $subcategory, $leaf], $first);
        self::assertSame($first, $second);
    }

    public function testAClosureCannotReadTheTrailItIsAddedTo(): void
    {
        $trail = new BreadcrumbTrail();
        $trail->append(static fn (): array => $trail->all());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot read the trail it is added to');

        $trail->all();
    }

    public function testAClosureReturningSomethingElseThanCrumbsIsRejected(): void
    {
        $trail = new BreadcrumbTrail();
        $trail->append(static fn (): array => ['leaf']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('but it holds a "string"');

        $trail->all();
    }

    public function testAClosureReturningANonIterableIsRejected(): void
    {
        $trail = new BreadcrumbTrail();
        $trail->append(static fn (): Breadcrumb => new Breadcrumb('leaf'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(\sprintf('"%s" given', Breadcrumb::class));

        $trail->all();
    }

    public function testAppendWithNoArgumentsIsANoop(): void
    {
        $trail = new BreadcrumbTrail();
        $trail->append();
        $trail->prepend();

        self::assertTrue($trail->isEmpty());
    }
}
