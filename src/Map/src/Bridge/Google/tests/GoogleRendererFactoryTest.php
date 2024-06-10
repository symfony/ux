<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Tests;

use Symfony\UX\Map\Bridge\Google\Renderer\GoogleRendererFactory;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Test\RendererFactoryTestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

final class GoogleRendererFactoryTest extends RendererFactoryTestCase
{
    public function createRendererFactory(): RendererFactoryInterface
    {
        return new GoogleRendererFactory(new StimulusHelper(null));
    }

    public static function supportsRenderer(): iterable
    {
        yield [true, 'google://GOOGLE_MAPS_API_KEY@default'];
        yield [false, 'somethingElse://login:apiKey@default'];
    }

    public static function createRenderer(): iterable
    {
        yield [
            'google://*******************@default/?version=weekly',
            'google://GOOGLE_MAPS_API_KEY@default',
        ];

        yield [
            'google://*******************@default/?version=quartly',
            'google://GOOGLE_MAPS_API_KEY@default?version=quartly',
        ];
    }

    public static function unsupportedSchemeRenderer(): iterable
    {
        yield ['somethingElse://foo@default'];
    }
}
