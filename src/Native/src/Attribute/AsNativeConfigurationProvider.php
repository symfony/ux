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
 * Marks a class as a Hotwire Native configuration provider.
 *
 * The class should contain one or more methods annotated with
 * {@see AsNativeConfiguration} that return Configuration instances.
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
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsNativeConfigurationProvider
{
}
