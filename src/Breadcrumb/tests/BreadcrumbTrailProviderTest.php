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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;

#[CoversClass(BreadcrumbTrailProvider::class)]
final class BreadcrumbTrailProviderTest extends TestCase
{
    public function testTheMainRequestTrailIsReturned(): void
    {
        $trail = new BreadcrumbTrail('product_edit');
        $requestStack = new RequestStack();
        $requestStack->push($this->mainRequest($trail));
        $provider = new BreadcrumbTrailProvider($requestStack);

        $provided = $provider->getTrail();

        self::assertSame($trail, $provided);
    }

    public function testAFragmentSubRequestGetsTheTrailOfThePage(): void
    {
        $trail = new BreadcrumbTrail('product_edit');
        $requestStack = new RequestStack();
        $requestStack->push($this->mainRequest($trail));
        $requestStack->push(Request::create('/_fragment'));
        $provider = new BreadcrumbTrailProvider($requestStack);

        $provided = $provider->getTrail();

        self::assertSame($trail, $provided);
    }

    public function testAnErrorPageDoesNotGetTheTrailOfThePageThatFailed(): void
    {
        $main = $this->mainRequest(new BreadcrumbTrail('product_edit'));
        $requestStack = new RequestStack();
        $requestStack->push($main);
        $requestStack->push($main->duplicate(null, null, ['exception' => new AccessDeniedHttpException()]));
        $provider = new BreadcrumbTrailProvider($requestStack);

        $provided = $provider->getTrail();

        self::assertNull($provided);
    }

    private function mainRequest(BreadcrumbTrail $trail): Request
    {
        $request = Request::create('/products/blue-sneakers/edit');
        $request->attributes->set(BreadcrumbTrail::ATTRIBUTE, $trail);

        return $request;
    }
}
