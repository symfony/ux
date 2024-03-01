<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\TwigComponent\ComponentMetadata;

class LiveComponentMetadataTest extends TestCase
{
    public function testGetOnlyPropsThatAcceptUpdatesFromParent()
    {
        $propMetadatas = [
            new LivePropMetadata('noUpdateFromParent1', new LiveProp(updateFromParent: false), null, false, false, null, false),
            new LivePropMetadata('noUpdateFromParent2', new LiveProp(updateFromParent: false), null, false, false, null, false),
            new LivePropMetadata('yesUpdateFromParent1', new LiveProp(updateFromParent: true), null, false, false, null, false),
            new LivePropMetadata('yesUpdateFromParent2', new LiveProp(updateFromParent: true), null, false, false, null, false),
        ];
        $liveComponentMetadata = new LiveComponentMetadata(new ComponentMetadata([]), $propMetadatas);
        $inputProps = [
            'noUpdateFromParent1' => 'foo',
            'yesUpdateFromParent1' => 'bar',
            'totallyUnrelated' => 'baz',
        ];
        $expected = ['yesUpdateFromParent1' => 'bar'];
        $actual = $liveComponentMetadata->getOnlyPropsThatAcceptUpdatesFromParent($inputProps);
        $this->assertEquals($expected, $actual);
    }
}
