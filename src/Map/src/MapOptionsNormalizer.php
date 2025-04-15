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

namespace Symfony\UX\Map;

use Symfony\UX\Map\Bridge as MapBridge;
use Symfony\UX\Map\Exception\UnableToDenormalizeOptionsException;
use Symfony\UX\Map\Exception\UnableToNormalizeOptionsException;

/**
 * Normalizes and denormalizes map options.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class MapOptionsNormalizer
{
    /**
     * @var string
     */
    private const KEY_PROVIDER = '@provider';

    /**
     * @var array<string, class-string<MapOptionsInterface>>
     */
    public static array $providers = [
        'google' => MapBridge\Google\GoogleOptions::class,
        'leaflet' => MapBridge\Leaflet\LeafletOptions::class,
    ];

    public static function denormalize(array $array): MapOptionsInterface
    {
        if (null === ($provider = $array[self::KEY_PROVIDER] ?? null)) {
            throw UnableToDenormalizeOptionsException::missingProviderKey(self::KEY_PROVIDER);
        }

        unset($array[self::KEY_PROVIDER]);

        if (null === $class = self::$providers[$provider] ?? null) {
            throw UnableToDenormalizeOptionsException::unsupportedProvider($provider, array_keys(self::$providers));
        }

        return $class::fromArray($array);
    }

    public static function normalize(MapOptionsInterface $options): array
    {
        $provider = array_search($options::class, self::$providers, true);
        if (!\is_string($provider)) {
            throw UnableToNormalizeOptionsException::unsupportedProviderClass($options::class);
        }

        $array = $options->toArray();
        $array[self::KEY_PROVIDER] = $provider;

        return $array;
    }
}
