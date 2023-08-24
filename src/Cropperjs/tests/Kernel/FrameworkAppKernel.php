<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Cropperjs\CropperjsBundle;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class FrameworkAppKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new CropperjsBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $frameworkConfig = [
                'secret' => '$ecret',
                'test' => true,
                'http_method_override' => false,
                'php_errors' => ['log' => true],
                'validation' => [
                    'email_validation_mode' => 'html5',
                ],
            ];

            if (self::VERSION_ID >= 60200) {
                $frameworkConfig['handle_all_throwables'] = true;
            }

            $container->loadFromExtension('framework', $frameworkConfig);
        });
    }
}
