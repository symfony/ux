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
use Symfony\UX\Breadcrumb\Exception\LogicException;
use Symfony\UX\Breadcrumb\ParentCrumbChecker;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;

#[CoversClass(ParentCrumbChecker::class)]
final class ParentCrumbCheckerTest extends TestCase
{
    public function testAPlaceholderReadingAnArgumentTheActionHasIsAccepted(): void
    {
        $checker = new ParentCrumbChecker();
        $ancestor = new Breadcrumb('{name:product}');

        $this->expectNotToPerformAssertions();

        $checker->check($ancestor, 'App\ProductController', 'App\ReviewController::__invoke()', ['product' => new Product()], []);
    }

    public function testAPlaceholderReadingAnArgumentTheActionLacksIsRejected(): void
    {
        $checker = new ParentCrumbChecker();
        $ancestor = new Breadcrumb('{name:product}');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The breadcrumb "{name:product}" of "App\ProductController" reads the argument "$product", which "App\ReviewController::__invoke()" does not have.');

        $checker->check($ancestor, 'App\ProductController', 'App\ReviewController::__invoke()', [], ['product' => 'blue-sneakers']);
    }

    public function testABarePlaceholderMayReadARouteParameter(): void
    {
        $checker = new ParentCrumbChecker();
        $ancestor = new Breadcrumb('{slug}');

        $this->expectNotToPerformAssertions();

        $checker->check($ancestor, 'App\ProductController', 'App\ReviewController::__invoke()', [], ['slug' => 'blue-sneakers']);
    }

    public function testABarePlaceholderReadingNothingIsRejected(): void
    {
        $checker = new ParentCrumbChecker();
        $ancestor = new Breadcrumb('{slug}');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The breadcrumb "{slug}" of "App\ProductController" reads "{slug}", which is neither an argument of "App\ReviewController::__invoke()" nor a parameter of its route.');

        $checker->check($ancestor, 'App\ProductController', 'App\ReviewController::__invoke()', ['product' => new Product()], ['id' => 42]);
    }

    public function testALiteralLabelIsNotChecked(): void
    {
        $checker = new ParentCrumbChecker();
        $ancestor = new Breadcrumb('{slug}', translationDomain: false);

        $this->expectNotToPerformAssertions();

        $checker->check($ancestor, 'App\ProductController', 'App\ReviewController::__invoke()', [], []);
    }
}
