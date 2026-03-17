<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Attribute;

/**
 * Marks a method as a Hotwire Native configuration factory.
 *
 * The method must return a {@see \Symfony\UX\Native\Configuration\Configuration} instance.
 * The containing class must be annotated with {@see AsNativeConfigurationProvider}.
 *
 * @example
 *
 *     #[AsNativeConfigurationProvider]
 *     class AppNativeConfiguration
 *     {
 *         #[AsNativeConfiguration('/config/ios_v1.json')]
 *         public function iosV1(): Configuration
 *         {
 *             return new Configuration(...);
 *         }
 *     }
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class AsNativeConfiguration
{
    public function __construct(
        public readonly string $path,
    ) {
    }
}
