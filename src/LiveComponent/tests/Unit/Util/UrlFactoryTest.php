<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Util\UrlFactory;

class UrlFactoryTest extends TestCase
{
    public static function provideTestCreate(): \Generator
    {
        yield 'keep_default_url' => [];

        yield 'keep_relative_url' => [
            'input' => ['previousUrl' => '/foo/bar'],
            'expectedUrl' => '/foo/bar',
        ];

        yield 'keep_absolute_url' => [
            'input' => ['previousUrl' => 'https://symfony.com/foo/bar'],
            'expectedUrl' => '/foo/bar',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/bar',
            ],
        ];

        yield 'keep_url_with_query_parameters' => [
            'input' => ['previousUrl' => 'https://symfony.com/foo/bar?prop1=val1&prop2=val2'],
            'expectedUrl' => '/foo/bar?prop1=val1&prop2=val2',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/bar',
            ],
        ];

        yield 'add_query_parameters' => [
            'input' => [
                'previousUrl' => '/foo/bar',
                'queryMappedProps' => ['prop1' => 'val1', 'prop2' => 'val2'],
            ],
            'expectedUrl' => '/foo/bar?prop1=val1&prop2=val2',
        ];

        yield 'override_previous_matching_query_parameters' => [
            'input' => [
                'previousUrl' => '/foo/bar?prop1=oldValue&prop3=oldValue',
                'queryMappedProps' => ['prop1' => 'val1', 'prop2' => 'val2'],
            ],
            'expectedUrl' => '/foo/bar?prop1=val1&prop3=oldValue&prop2=val2',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/bar',
            ],
        ];

        yield 'add_path_parameters' => [
            'input' => [
                'previousUrl' => '/foo/bar',
                'pathMappedProps' => ['value' => 'baz'],
            ],
            'expectedUrl' => '/foo/baz',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/baz',
                'props' => ['value' => 'baz'],
            ],
        ];

        yield 'add_both_parameters' => [
            'input' => [
                'previousUrl' => '/foo/bar',
                'pathMappedProps' => ['value' => 'baz'],
                'queryMappedProps' => ['filter' => 'all'],
            ],
            'expectedUrl' => '/foo/baz?filter=all',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/baz',
                'props' => ['value' => 'baz'],
            ],
        ];

        yield 'handle_path_parameter_not_recognized' => [
            'input' => [
                'previousUrl' => '/foo/bar',
                'pathMappedProps' => ['value' => 'baz'],
            ],
            'expectedUrl' => '/foo/bar?value=baz',
            'routerStubData' => [
                'previousUrl' => '/foo/bar',
                'newUrl' => '/foo/bar?value=baz',
                'props' => ['value' => 'baz'],
            ],
        ];
    }

    /**
     * @dataProvider provideTestCreate
     */
    public function testCreate(
        array $input = [],
        string $expectedUrl = '',
        array $routerStubData = [],
    ): void {
        $previousUrl = $input['previousUrl'] ?? '';
        $router = $this->createRouterStub(
            $routerStubData['previousUrl'] ?? $previousUrl,
            $routerStubData['newUrl'] ?? $previousUrl,
            $routerStubData['props'] ?? [],
        );
        $factory = new UrlFactory($router);
        $newUrl = $factory->createFromPreviousAndProps(
            $previousUrl,
            $input['pathMappedProps'] ?? [],
            $input['queryMappedProps'] ?? []
        );

        $this->assertEquals($expectedUrl, $newUrl);
    }

    public function testResourceNotFoundException()
    {
        $previousUrl = '/foo/bar';
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('match')
            ->with($previousUrl)
            ->willThrowException(new ResourceNotFoundException());
        $factory = new UrlFactory($router);

        $this->assertNull($factory->createFromPreviousAndProps($previousUrl, [], []));
    }

    public function testMethodNotAllowedException()
    {
        $previousUrl = '/foo/bar';
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('match')
            ->with($previousUrl)
            ->willThrowException(new MethodNotAllowedException(['GET']));
        $factory = new UrlFactory($router);

        $this->assertNull($factory->createFromPreviousAndProps($previousUrl, [], []));
    }

    public function testMissingMandatoryParametersException()
    {
        $previousUrl = '/foo/bar';
        $matchedRouteName = 'foo_bar';
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('match')
            ->with($previousUrl)
            ->willReturn(['_route' => $matchedRouteName]);
        $router->expects(self::once())
            ->method('generate')
            ->with($matchedRouteName, [])
            ->willThrowException(new MissingMandatoryParametersException($matchedRouteName, ['baz']));
        $factory = new UrlFactory($router);

        $this->assertNull($factory->createFromPreviousAndProps($previousUrl, [], []));
    }

    private function createRouterStub(
        string $previousUrl,
        string $newUrl,
        array $props = [],
    ): RouterInterface {
        $matchedRoute = 'default';
        $context = $this->createMock(RequestContext::class);
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $router->expects(self::exactly(2))
            ->method('setContext');
        $router->expects(self::once())
            ->method('match')
            ->with($previousUrl)
            ->willReturn(['_route' => $matchedRoute]);
        $router->expects(self::once())
            ->method('generate')
            ->with($matchedRoute, $props)
            ->willReturn($newUrl);

        return $router;
    }
}
