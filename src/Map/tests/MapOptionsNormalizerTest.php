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

namespace Symfony\UX\Map\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\UnableToDenormalizeOptionsException;
use Symfony\UX\Map\MapOptionsNormalizer;

final class MapOptionsNormalizerTest extends TestCase
{
    protected function tearDown(): void
    {
        DummyOptions::unregisterFromNormalizer();
    }

    public function testDenormalizingWhenProviderKeyIsMissing(): void
    {
        $this->expectException(UnableToDenormalizeOptionsException::class);
        $this->expectExceptionMessage(' the provider key "@provider" is missing in the normalized options.');

        MapOptionsNormalizer::denormalize([]);
    }

    public function testDenormalizingWhenProviderIsNotSupported(): void
    {
        $this->expectException(UnableToDenormalizeOptionsException::class);
        $this->expectExceptionMessage(' the provider "foo" is not supported. Supported providers are "google", "leaflet".');

        MapOptionsNormalizer::denormalize(['@provider' => 'foo']);
    }

    public function testDenormalizingAndNormalizing(): void
    {
        DummyOptions::registerToNormalizer();

        $options = MapOptionsNormalizer::denormalize([
            '@provider' => 'dummy',
            'mapId' => 'abcdef',
            'mapType' => 'satellite',
        ]);

        self::assertInstanceOf(DummyOptions::class, $options);
        self::assertEquals([
            'mapId' => 'abcdef',
            'mapType' => 'satellite',
        ], $options->toArray());

        self::assertEquals([
            '@provider' => 'dummy',
            'mapId' => 'abcdef',
            'mapType' => 'satellite',
        ], MapOptionsNormalizer::normalize($options));

        self::assertEquals($options, MapOptionsNormalizer::denormalize(MapOptionsNormalizer::normalize($options)));
    }
}
