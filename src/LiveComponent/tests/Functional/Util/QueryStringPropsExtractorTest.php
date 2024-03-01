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
use Symfony\UX\LiveComponent\Util\QueryStringPropsExtractor;

class QueryStringPropsExtractorTest extends KernelTestCase
{
    use LiveComponentTestHelper;

    /**
     * @dataProvider getQueryStringTests
     */
    public function testExtract(string $queryString, array $expected)
    {
        $extractor = new QueryStringPropsExtractor($this->hydrator());

        $request = Request::create('/'.!empty($queryString) ? '?'.$queryString : '');

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
            'empty value for nullable string' => ['prop1=', ['prop1' => null]],
            'string value' => ['prop1=foo', ['prop1' => 'foo']],
            'empty value for nullable int' => ['prop2=', ['prop2' => null]],
            'int value' => ['prop2=42', ['prop2' => 42]],
            'array value' => ['prop3[]=foo&prop3[]=bar', ['prop3' => ['foo', 'bar']]],
            'array value indexed' => ['prop3[1]=foo&prop3[0]=bar', ['prop3' => [1 => 'foo', 0 => 'bar']]],
            'not bound prop' => ['prop4=foo', []],
            'object value' => ['prop5[address]=foo&prop5[city]=bar', ['prop5' => (function () {
                $address = new Address();
                $address->address = 'foo';
                $address->city = 'bar';

                return $address;
            })(),
            ]],
            'invalid scalar value' => ['prop1[]=foo&prop1[]=bar', []],
            'invalid array value' => ['prop3=foo', []],
            'invalid object value' => ['prop5=foo', []],
        ];
    }
}
