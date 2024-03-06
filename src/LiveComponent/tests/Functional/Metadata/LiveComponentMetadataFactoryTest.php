<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Metadata;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\ComponentWithUrlBoundProps;

class LiveComponentMetadataFactoryTest extends KernelTestCase
{
    public function testQueryStringMapping()
    {
        /** @var LiveComponentMetadataFactory $metadataFactory */
        $metadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');

        $class = new \ReflectionClass(ComponentWithUrlBoundProps::class);
        $propsMetadata = $metadataFactory->createPropMetadatas($class);

        $propsMetadataByName = [];
        foreach ($propsMetadata as $propMetadata) {
            $propsMetadataByName[$propMetadata->getName()] = $propMetadata;
        }

        $this->assertNotNull($propsMetadataByName['stringProp']->urlMapping());
        $this->assertNotNull($propsMetadataByName['intProp']->urlMapping());
        $this->assertNotNull($propsMetadataByName['arrayProp']->urlMapping());
        $this->assertNull($propsMetadataByName['unboundProp']->urlMapping());
        $this->assertNotNull($propsMetadataByName['objectProp']->urlMapping());
        $this->assertNotNull($propsMetadataByName['propWithField1']->urlMapping());
        $this->assertNotNull($propsMetadataByName['propWithField2']->urlMapping());
        $this->assertNull($propsMetadataByName['maybeBoundProp']->urlMapping());
        $this->assertEquals(new UrlMapping(as: 'q'), $propsMetadataByName['boundPropWithAlias']->urlMapping());
        $this->assertNotNull($propsMetadataByName['boundPropWithCustomAlias']);
    }
}
