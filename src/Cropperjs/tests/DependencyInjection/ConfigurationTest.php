<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\UX\Cropperjs\DependencyInjection\Configuration;

/**
 * @internal
 */
class ConfigurationTest extends TestCase
{
    private function process(array $config): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), [$config]);
    }

    public function testDefaultConfiguration()
    {
        $config = $this->process([]);

        $this->assertSame('gd', $config['driver']);
        $this->assertNull($config['driver_service']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidDrivers(): iterable
    {
        yield 'gd' => ['gd'];
        yield 'imagick' => ['imagick'];
        yield 'vips' => ['vips'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidDrivers')]
    public function testValidDriversAreAccepted(string $driver)
    {
        $config = $this->process(['driver' => $driver]);

        $this->assertSame($driver, $config['driver']);
    }

    public function testInvalidDriverIsRejected()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['driver' => 'bogus']);
    }
}
