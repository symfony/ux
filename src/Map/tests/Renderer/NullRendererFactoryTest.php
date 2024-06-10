<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Renderer;

use Symfony\UX\Map\Renderer\NullRendererFactory;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Test\RendererFactoryTestCase;

final class NullRendererFactoryTest extends RendererFactoryTestCase
{
    public function createRendererFactory(): RendererFactoryInterface
    {
        return new NullRendererFactory();
    }

    public static function supportsRenderer(): iterable
    {
        yield [true, 'null://null'];
        yield [true, 'null://foobar'];
        yield [false, 'google://GOOGLE_MAPS_API_KEY@default'];
        yield [false, 'leaflet://default'];
    }

    public static function createRenderer(): iterable
    {
        yield [
            'null://null',
            'null://null',
        ];
    }

    public static function unsupportedSchemeRenderer(): iterable
    {
        yield ['somethingElse://foo@default'];
    }
}
