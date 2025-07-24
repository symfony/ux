<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\UX\LiveComponent\EventListener\LiveUrlSubscriber;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\LiveComponent\Util\UrlFactory;

class LiveUrlSubscriberTest extends TestCase
{
    public function getIgnoreData(): iterable
    {
        yield 'not_a_live_component' => [
            'attributes' => [],
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'headers' => ['X-Live-Url' => '/foo/bar'],
        ];
        yield 'not_main_request' => [
            'attributes' => ['_live_component' => 'componentName'],
            'requestType' => HttpKernelInterface::SUB_REQUEST,
            'headers' => ['X-Live-Url' => '/foo/bar'],
        ];
        yield 'no_previous_url' => [
            'attributes' => ['_live_component' => 'componentName'],
            'requestType' => HttpKernelInterface::MAIN_REQUEST,
            'headers' => [],
        ];
    }

    /**
     * @dataProvider getIgnoreData
     */
    public function testDoNothing(
        array $attributes = ['_live_component' => 'componentName'],
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        array $headers = ['X-Live-Url' => '/foo/bar'],
    ): void {
        $request = new Request();
        $request->attributes->add($attributes);
        $request->headers->add($headers);
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType,
            $response
        );

        $metadataFactory = $this->createMock(LiveComponentMetadataFactory::class);
        $metadataFactory->expects(self::never())->method('getMetadata');
        $urlFactory = $this->createMock(UrlFactory::class);
        $urlFactory->expects(self::never())->method('createFromPreviousAndProps');
        $liveUrlSubscriber = new LiveUrlSubscriber($metadataFactory, $urlFactory);

        $liveUrlSubscriber->onKernelResponse($event);
        $this->assertNull($response->headers->get('X-Live-Url'));
    }

    public static function provideTestUrlFactoryReceivesPathAndQuertyPropsFromRequestData(): iterable
    {
        yield 'prop_without_matching_property' => [
            'liveRequestData' => [
                'props' => ['notMatchingProp' => 0],
            ],
        ];
        yield 'prop_matching_non_mapped_property' => [
            'liveRequestData' => [
                'props' => ['nonMappedProp' => 0],
            ],
        ];
        yield 'props_matching_query_mapped_properties' => [
            'liveRequestData' => [
                'props' => ['queryMappedProp1' => 1],
                'updated' => ['queryMappedProp2' => 2],
                'responseProps' => ['queryMappedProp3' => 3],
            ],
            'expectedPathProps' => [],
            'expectedQueryProps' => [
                'queryMappedProp1' => 1,
                'queryMappedProp2' => 2,
                'queryMappedProp3' => 3,
            ],
        ];
        yield 'props_matching_path_mapped_properties' => [
            'liveRequestData' => [
                'props' => ['pathMappedProp1' => 1],
                'updated' => ['pathMappedProp2' => 2],
                'responseProps' => ['pathMappedProp3' => 3],
            ],
            'expectedPathProps' => [
                'pathMappedProp1' => 1,
                'pathMappedProp2' => 2,
                'pathMappedProp3' => 3,
            ],
            'expectedQueryProps' => [],
        ];
        yield 'props_matching_properties_with_alias' => [
            'liveRequestData' => [
                'props' => ['pathMappedPropWithAlias' => 1, 'queryMappedPropWithAlias' => 2],
            ],
            'expectedPathProps' => ['pathAlias' => 1],
            'expectedQueryProps' => ['queryAlias' => 2],
        ];
        yield 'responseProps_have_highest_priority' => [
            'liveRequestData' => [
                'props' => ['queryMappedProp1' => 1],
                'updated' => ['queryMappedProp1' => 2],
                'responseProps' => ['queryMappedProp1' => 3],
            ],
            'expectedPathProps' => [],
            'expectedQueryProps' => ['queryMappedProp1' => 3],
        ];
        yield 'updated_have_second_priority' => [
            'liveRequestData' => [
                'props' => ['queryMappedProp1' => 1],
                'updated' => ['queryMappedProp1' => 2],
            ],
            'expectedPathProps' => [],
            'expectedQueryProps' => ['queryMappedProp1' => 2],
        ];
    }

    /**
     * @dataProvider provideTestUrlFactoryReceivesPathAndQuertyPropsFromRequestData
     */
    public function testUrlFactoryReceivesPathAndQuertyPropsFromRequestData(
        array $liveRequestData,
        array $expectedPathProps = [],
        array $expectedQueryProps = [],
    ): void {
        $previousLocation = '/foo/bar';
        $newLocation = '/foo/baz';
        $componentName = 'componentName';
        $component = $this->createMock(\stdClass::class);
        $metaData = $this->createMock(LiveComponentMetadata::class);
        $metaData->expects(self::once())
            ->method('getAllUrlMappings')
            ->willReturn([
                'nonMappedProp' => false,
                'queryMappedProp1' => new UrlMapping(),
                'queryMappedProp2' => new UrlMapping(),
                'queryMappedProp3' => new UrlMapping(),
                'pathMappedProp1' => new UrlMapping(mapPath: true),
                'pathMappedProp2' => new UrlMapping(mapPath: true),
                'pathMappedProp3' => new UrlMapping(mapPath: true),
                'queryMappedPropWithAlias' => new UrlMapping(as: 'queryAlias'),
                'pathMappedPropWithAlias' => new UrlMapping(as: 'pathAlias', mapPath: true),
            ]);
        $request = new Request();
        $request->attributes->add([
            '_live_component' => $componentName,
            '_mounted_component' => $component,
            '_live_request_data' => $liveRequestData,
        ]);
        $request->headers->add(['X-Live-Url' => $previousLocation]);
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $metadataFactory = $this->createMock(LiveComponentMetadataFactory::class);
        $metadataFactory->expects(self::once())->method('getMetadata')->with($componentName)->willReturn($metaData);
        $urlFactory = $this->createMock(UrlFactory::class);
        $liveUrlSubscriber = new LiveUrlSubscriber($metadataFactory, $urlFactory);

        $urlFactory->expects(self::once())
            ->method('createFromPreviousAndProps')
            ->with($previousLocation, $expectedPathProps, $expectedQueryProps)
            ->willReturn($newLocation);
        $liveUrlSubscriber->onKernelResponse($event);
        $this->assertEquals($newLocation, $response->headers->get('X-Live-Url'));
    }
}
