<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Tests;

use Symfony\UX\Map\Bridge\Leaflet\Renderer\LeafletRendererFactory;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Test\RendererFactoryTestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

final class LeafletRendererFactoryTest extends RendererFactoryTestCase
{
    public function createRendererFactory(): RendererFactoryInterface
    {
        return new LeafletRendererFactory(new StimulusHelper(null));
    }

    public static function supportsRenderer(): iterable
    {
        yield [true, 'leaflet://default'];
        yield [false, 'foo://default'];
    }

    public static function createRenderer(): iterable
    {
        yield [
            'leaflet://default',
            'leaflet://default',
        ];
    }

    public static function unsupportedSchemeRenderer(): iterable
    {
        yield ['somethingElse://foo@default'];
    }
}
