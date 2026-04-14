<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests;

use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

final class IosConfigurationFactory
{
    public static function v1(): Configuration
    {
        return new Configuration(
            [
                'use_local_db' => true,
                'cable' => ['script_url' => 'https://hotwire-native-demo.dev/configurations/action_cable.js'],
            ],
            [
                new Rule(
                    ['.*'],
                    ['context' => 'default', 'pull_to_refresh_enabled' => true]
                ),
            ]
        );
    }
}
