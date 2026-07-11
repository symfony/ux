<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\UX\Editor\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testDefaults()
    {
        $cfg = new Processor()->processConfiguration(new Configuration(), [[]]);
        self::assertTrue($cfg['html']['sanitize_required']);
        self::assertSame('default', $cfg['upload']['default_profile']);
        self::assertSame(3600, $cfg['upload']['ttl_seconds']);
    }

    public function testOverrides()
    {
        $cfg = new Processor()->processConfiguration(new Configuration(), [[
            'html' => ['sanitize_required' => false],
            'upload' => ['default_profile' => 'images', 'ttl_seconds' => 600],
        ]]);
        self::assertFalse($cfg['html']['sanitize_required']);
        self::assertSame('images', $cfg['upload']['default_profile']);
        self::assertSame(600, $cfg['upload']['ttl_seconds']);
    }
}
