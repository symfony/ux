<?php

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enables using the getContainer() method in Symfony < 5.3.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ContainerBC
{
    protected static function getContainer(): ContainerInterface
    {
        if (!method_exists(parent::class, 'getContainer')) {
            if (!static::$booted) {
                static::bootKernel();
            }

            return self::$container;
        }

        return parent::getContainer();
    }
}
