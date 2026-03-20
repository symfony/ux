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

namespace Symfony\UX\Native\Tests;

use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

#[AsNativeConfigurationProvider]
final class AndroidConfigurationFactory
{
    #[AsNativeConfiguration('/config/android_v1.json')]
    public function v1(): Configuration
    {
        return new Configuration(
            settings: [
                'use_local_db' => false,
            ],
            rules: [
                new Rule(
                    patterns: ['/articles/.*'],
                    properties: ['context' => 'default', 'pull_to_refresh_enabled' => false],
                ),
            ],
        );
    }
}
