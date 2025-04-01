<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Util;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Address;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Symfony\UX\LiveComponent\Util\RequestPropsExtractor;

class RequestPropsExtractorTest extends KernelTestCase
{
    use LiveComponentTestHelper;

    /**
     * @dataProvider getQueryStringTests
     */
    public function testExtractFromQueryString(string $queryString, array $expected, array $attributes = []): void
    {
        $extractor = new RequestPropsExtractor($this->hydrator());

        $request = Request::create('/'.!empty($queryString) ? '?'.$queryString : '');
        $request->attributes->add($attributes);

        /** @var LiveComponentMetadataFactory $metadataFactory */
        $metadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');

        $metadata = $metadataFactory->getMetadata('component_with_url_bound_props');
        $component = $this->getComponent('component_with_url_bound_props');

        $data = $extractor->extract($request, $metadata, $component);

        $this->assertEquals($expected, $data);
    }

    public function getQueryStringTests(): iterable
    {
        yield from [
            'no query string' => ['', []],
            'empty value for nullable string' => ['stringProp=', ['stringProp' => null]],
            'string value' => ['stringProp=foo', ['stringProp' => 'foo']],
            'empty value for nullable int' => ['intProp=', ['intProp' => null]],
            'int value' => ['intProp=42', ['intProp' => 42]],
            'array value' => ['arrayProp[]=foo&arrayProp[]=bar', ['arrayProp' => ['foo', 'bar']]],
            'array value indexed' => ['arrayProp[1]=foo&arrayProp[0]=bar', ['arrayProp' => [1 => 'foo', 0 => 'bar']]],
            'not bound prop' => ['unboundProp=foo', []],
            'object value' => ['objectProp[address]=foo&objectProp[city]=bar', ['objectProp' => (function () {
                $address = new Address();
                $address->address = 'foo';
                $address->city = 'bar';

                return $address;
            })(),
            ]],
            'invalid scalar value' => ['stringProp[]=foo&stringProp[]=bar', []],
            'invalid array value' => ['arrayProp=foo', []],
            'invalid object value' => ['objectProp=foo', []],
            'aliased prop' => ['q=foo', ['boundPropWithAlias' => 'foo']],
            'attribute prop' => ['', ['stringProp' => 'foo'], ['stringProp' => 'foo']],
            'attribute aliased prop' => ['', ['boundPropWithAlias' => 'foo'], ['q' => 'foo']],
            'attribute not bound prop' => ['', [], ['unboundProp' => 'foo']],
            'query priority' => ['stringProp=foo', ['stringProp' => 'foo'], ['stringProp' => 'bar']],
        ];
    }
}
