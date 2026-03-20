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

namespace Symfony\UX\Native\Tests\Fixtures;

use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Configuration\Configuration;

#[AsNativeConfigurationProvider]
final class StaticMethodConfigurationProvider
{
    #[AsNativeConfiguration('/config/static.json')]
    public static function v1(): Configuration
    {
        return new Configuration(settings: ['static' => true]);
    }
}
