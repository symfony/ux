<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Toolkit\Command\DebugKitCommand;
use Symfony\UX\Toolkit\Command\InstallComponentCommand;
use Symfony\UX\Toolkit\Command\LintKitCommand;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Registry\GitHubRegistry;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Registry\RegistryFactory;
use Symfony\UX\Toolkit\Registry\Type;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        // Commands

        ->set('.ux_toolkit.command.debug_kit', DebugKitCommand::class)
            ->args([
                service('.ux_toolkit.registry.factory'),
            ])
            ->tag('console.command')

        ->set('.ux_toolkit.command.install', InstallComponentCommand::class)
            ->args([
                param('ux_toolkit.kit'),
                service('.ux_toolkit.registry.factory'),
                service('filesystem'),
            ])
            ->tag('console.command')

        ->set('.ux_toolkit.command.lint_kit', LintKitCommand::class)
            ->args([
                service('.ux_toolkit.registry.factory'),
            ])
            ->tag('console.command')

        // Registry

        ->set('.ux_toolkit.registry.factory', RegistryFactory::class)
            ->args([
                service_locator([
                    Type::Local->value => service('.ux_toolkit.registry.local'),
                    Type::GitHub->value => service('.ux_toolkit.registry.github'),
                ]),
            ])

        ->set('.ux_toolkit.registry.local', LocalRegistry::class)
            ->args([
                service('.ux_toolkit.kit.kit_factory'),
                service('filesystem'),
                param('kernel.project_dir'),
            ])

        ->set('.ux_toolkit.registry.github', GitHubRegistry::class)
            ->args([
                service('.ux_toolkit.kit.kit_factory'),
                service('filesystem'),
                service('http_client')->nullOnInvalid(),
            ])

        // Kit

        ->set('.ux_toolkit.kit.kit_factory', KitFactory::class)
            ->args([
                service('filesystem'),
                service('.ux_toolkit.kit.kit_synchronizer'),
            ])

        ->set('.ux_toolkit.kit.kit_synchronizer', KitSynchronizer::class)
            ->args([
                service('filesystem'),
            ])
    ;
};
