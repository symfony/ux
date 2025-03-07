<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
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
use Symfony\UX\Toolkit\DependencyInjection\ToolkitExtension;
use Symfony\UX\Toolkit\Registry\DependenciesResolver;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class UxToolkitBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ToolkitExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->autowire(OfficialRepository::class);
        $container->autowire(GithubRepository::class);
        $container->autowire(RepositoryFactory::class);
        $container->autowire(RepositoryIdentifier::class);
        $container->autowire(RegistryFactory::class);
        $container->autowire(DependenciesResolver::class);
        $container->autowire(RegistryCompiler::class);
        $container->autowire(Registry::class);

        $container->autowire(TwigComponentCompiler::class);
        $container->getDefinition(TwigComponentCompiler::class)
            ->setArguments([
                '$prefix' => '%ux_toolkit.prefix%',
            ]);

        // Prepare commands
        $this->addConsoleCommand($container, BuildRegistryCommand::class);
        $this->addConsoleCommand($container, UxToolkitInstallCommand::class);
        $this->addConsoleCommand($container, DebugUxToolkitCommand::class);

        // Inject http client (if exists) to github repository
        if ($container->has('http_client')) {
            $container->getDefinition(GithubRepository::class)
                ->setArgument('$httpClient', $container->get('http_client'));
        }

        // current theme
        $container->autowire(CurrentTheme::class);
        $container->getDefinition(CurrentTheme::class)
            ->setArguments([
                '$theme' => '%ux_toolkit.theme%',
            ]);

        // Make registry public (useful for exposing documentation)
        $container->getDefinition(Registry::class)->setPublic(true);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $classname
     * @return void
     */
    public function addConsoleCommand(ContainerBuilder $container, string $classname): void
    {
        $container->autowire($classname);
        $container
            ->registerForAutoconfiguration($classname)
            ->addTag('console.command');
        $container
            ->getDefinition($classname)
            ->setPublic(true)
            ->addTag('console.command');
    }

}
