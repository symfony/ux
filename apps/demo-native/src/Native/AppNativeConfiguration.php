<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Native;

use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

#[AsNativeConfigurationProvider]
final class AppNativeConfiguration
{
    #[AsNativeConfiguration(path: '/config/ios_v1.json')]
    public function iosV1(): Configuration
    {
        return new Configuration(
            rules: [
                new Rule(
                    patterns: ['.*'],
                    properties: [
                        'context' => 'default',
                        'pull_to_refresh_enabled' => true,
                    ],
                ),
                new Rule(
                    patterns: ['^/notes/new$'],
                    properties: [
                        'context' => 'modal',
                        'pull_to_refresh_enabled' => false,
                    ],
                ),
                new Rule(
                    patterns: ['^/notes/\\d+/edit$'],
                    properties: [
                        'context' => 'modal',
                        'pull_to_refresh_enabled' => false,
                    ],
                ),
            ],
        );
    }
}
