<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class QueryStringInitializerSubscriberTest extends KernelTestCase
{
    use HasBrowser;

    public function testQueryStringPropsInitialization()
    {
        $queryString = '?'
            .'stringProp=foo'
            .'&intProp=42'
            .'&arrayProp[]=foo&arrayProp[]=bar'
            .'&unboundProp=unbound'
            .'&objectProp[address]=foo&objectProp[city]=bar'
            .'&field1=foo'
            .'&field2=foo'
            .'&maybeBoundProp=foo'
            .'&q=foo'
            .'&customAlias=foo'
        ;
        $this->browser()
            ->get('/render-template/render_component_with_url_bound_props'.$queryString)
            ->assertSuccessful()
            ->assertContains('StringProp: foo')
            ->assertContains('IntProp: 42')
            ->assertContains('ArrayProp: foo,bar')
            ->assertContains('UnboundProp:')
            ->assertContains('ObjectProp: address: foo city: bar')
            ->assertContains('PropWithField1: foo')
            ->assertContains('PropWithField2: foo')
            ->assertContains('MaybeBoundProp: foo')
            ->assertContains('BoundPropWithAlias: foo')
            ->assertContains('BoundPropWithCustomAlias: foo')
        ;
    }
}
