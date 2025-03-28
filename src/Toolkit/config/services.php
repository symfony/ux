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

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */

use Symfony\UX\Toolkit\Command\BuildRegistryCommand;
use Symfony\UX\Toolkit\Command\DebugUxToolkitCommand;
use Symfony\UX\Toolkit\Command\UxToolkitInstallCommand;
use Symfony\UX\Toolkit\Compiler\RegistryCompiler;
use Symfony\UX\Toolkit\Compiler\TwigComponentCompiler;
use Symfony\UX\Toolkit\ComponentRepository\CurrentTheme;
use Symfony\UX\Toolkit\ComponentRepository\GithubRepository;
use Symfony\UX\Toolkit\ComponentRepository\OfficialRepository;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryFactory;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentifier;
use Symfony\UX\Toolkit\Registry\DependenciesResolver;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_toolkit.compiler.registry_compiler', RegistryCompiler::class)
            ->args([
                service('filesystem')
            ])

        ->set('.ux_toolkit.compiler.twig_component_compiler', TwigComponentCompiler::class)
            ->args([
                param('ux_toolkit.prefix'),
                service('.ux_toolkit.registry.dependencies_resolver'),
                service('filesystem'),
            ])

        ->set('.ux_toolkit.component_repository.official_repository', OfficialRepository::class)
            ->args([
                service('filesystem')
            ])

        ->set('.ux_toolkit.component_repository.github_repository', GithubRepository::class)
            ->args([
                service('filesystem'),
                service('http_client')->nullOnInvalid(),
            ])

        ->set('.ux_toolkit.component_repository.repository_factory', RepositoryFactory::class)
            ->args([
                service('.ux_toolkit.component_repository.official_repository'),
                service('.ux_toolkit.component_repository.github_repository'),
            ])

        ->set('.ux_toolkit.component_repository.current_theme', CurrentTheme::class)
            ->args([
                param('ux_toolkit.theme'),
                service('.ux_toolkit.component_repository.repository_factory'),
                service('.ux_toolkit.component_repository.repository_identifier'),
            ])

        ->set('.ux_toolkit.component_repository.repository_identifier', RepositoryIdentifier::class)

        ->set('.ux_toolkit.registry.dependencies_resolver', DependenciesResolver::class)

        ->set('.ux_toolkit.registry.registry_factory', RegistryFactory::class)

        ->set('.ux_toolkit.command.build_registry', BuildRegistryCommand::class)
            ->args([
                service('.ux_toolkit.compiler.registry_compiler')
            ])
            ->tag('console.command')

        ->set('.ux_toolkit.command.install', UxToolkitInstallCommand::class)
            ->args([
                service('.ux_toolkit.component_repository.current_theme'),
                service('.ux_toolkit.registry.registry_factory'),
                service('.ux_toolkit.compiler.twig_component_compiler'),
            ])
            ->tag('console.command')

        ->set('.ux_toolkit.command.debug', DebugUxToolkitCommand::class)
            ->args([
                service('.ux_toolkit.component_repository.current_theme'),
                service('.ux_toolkit.registry.registry_factory'),
            ])
            ->tag('console.command')
    ;
};
