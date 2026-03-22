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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Tests\AndroidConfigurationFactory;
use Symfony\UX\Native\Tests\IosConfigurationFactory;

return static function (ContainerConfigurator $container) {
    $services = $container
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $container->parameters()->set('kernel.debug', true);

    $container->import(__DIR__.'/../config/services.php');
    $container->import(__DIR__.'/../config/debug.php');

    $container->extension('ux_native', [
        'output_dir' => '%kernel.cache_dir%/output',
    ]);
    $container->extension('framework', [
        'test' => true,
        'secret' => 'test',
        'http_method_override' => true,
        'handle_all_throwables' => true,
        'router' => [
            'utf8' => true,
            'resource' => __DIR__.'/routes.php',
            'type' => 'php',
        ],
        'assets' => [
            'enabled' => true,
        ],
        'default_locale' => 'en',
        'php_errors' => [
            'log' => true,
        ],
    ]);

    $services->set(IosConfigurationFactory::class);
    $services->set('.ux_native.config.ios_v1', Configuration::class)
        ->factory([IosConfigurationFactory::class, 'v1'])
        ->tag('ux_native.configuration', ['path' => '/config/ios_v1.json'])
    ;

    // AndroidConfigurationFactory uses #[AsNativeConfiguration] attribute on methods
    $services->set(AndroidConfigurationFactory::class);
};
