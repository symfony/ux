<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Translator\UxTranslatorBundle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class FrameworkAppKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new UxTranslatorBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => '$ecret',
                'test' => true,
                'translator' => [
                    'fallbacks' => ['en'],
                    'default_path' => '%kernel.project_dir%/tests/Fixtures/translations',
                ],
                'enabled_locales' => ['en', 'fr'],
                'http_method_override' => false,
            ]);
        });
    }
}
