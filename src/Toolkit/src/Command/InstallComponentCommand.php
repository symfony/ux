<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Command;

use Composer\InstalledVersions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Component\ComponentInstaller;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Exception\ComponentAlreadyExistsException;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:install-component',
    description: 'This command will install a new UX Component in your project',
)]
class InstallComponentCommand extends Command
{
    private SymfonyStyle $io;
    private bool $isInteractive;

    public function __construct(
        private readonly string $kitName,
        private readonly RegistryFactory $registryFactory,
        private readonly ComponentInstaller $componentInstaller,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::REQUIRED, 'The component name (Ex: Button)')
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The destination directory',
                Path::join('templates', 'components')
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the component installation, even if the component already exists')
            ->addOption('kit', 'k', InputOption::VALUE_OPTIONAL, 'Override the kit name', $this->kitName)
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command will install a new UX Component in your project.

To install a component from your current kit, use:

<info>php %command.full_name% Button</info>

To install a component from an official UX Toolkit kit, use the <info>--kit</info> option:

<info>php %command.full_name% Button --kit=shadcn</info>

To install a component from an external GitHub kit, use the <info>--kit</info> option:

<info>php %command.full_name% Button --kit=https://github.com/user/repository@kit</info>
<info>php %command.full_name% Button --kit=https://github.com/user/repository@kit:branch</info>
EOF
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->isInteractive = $input->isInteractive();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitName = $input->getOption('kit');
        $registry = $this->registryFactory->getForKit($kitName);
        $kit = $registry->getKit($kitName);

        // Get the component name from the argument, or suggest alternatives if it doesn't exist
        if (null === $component = $kit->getComponent($componentName = $input->getArgument('component'))) {
            $message = \sprintf('The component "%s" does not exist.', $componentName);

            $alternativeComponents = $this->getAlternativeComponents($kit, $componentName);
            $alternativeComponentsCount = \count($alternativeComponents);

            if (1 === $alternativeComponentsCount && $input->isInteractive()) {
                $io->warning($message);
                if ($io->confirm(\sprintf('Do you want to install the component "%s" instead?', $alternativeComponents[0]->name))) {
                    $component = $alternativeComponents[0];
                } else {
                    return Command::FAILURE;
                }
            } elseif ($alternativeComponentsCount > 0) {
                $io->warning(\sprintf('%s'."\n".'Possible alternatives: "%s"', $message, implode('", "', array_map(fn (Component $c) => $c->name, $alternativeComponents))));

                return Command::FAILURE;
            } else {
                $io->error($message);

                return Command::FAILURE;
            }
        }

        // Install the component and dependencies
        $destination = $input->getOption('destination');

        if (!$this->installComponent($kit, $component, $destination)) {
            return Command::FAILURE;
        }

        // Iterate over the component's dependencies
        $phpDependenciesToInstall = [];
        foreach ($component->getDependencies() as $dependency) {
            if ($dependency instanceof ComponentDependency) {
                if (!$this->installComponent($kit, $kit->getComponent($dependency->name), $destination)) {
                    return Command::FAILURE;
                }
            } elseif ($dependency instanceof PhpPackageDependency && !InstalledVersions::isInstalled($dependency->name)) {
                $phpDependenciesToInstall[] = $dependency;
            }
        }

        $this->io->success(\sprintf('The component "%s" has been installed.', $component->name));

        if ([] !== $phpDependenciesToInstall) {
            $this->io->writeln(\sprintf('Run <info>composer require %s</info> to install the required PHP dependencies.', implode(' ', $phpDependenciesToInstall)));
            $this->io->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Get alternative components that are similar to the given component name.
     */
    private function getAlternativeComponents(Kit $kit, string $componentName): array
    {
        $alternative = [];

        foreach ($kit->getComponents() as $component) {
            $lev = levenshtein($componentName, $component->name, 2, 5, 10);
            if ($lev <= 8 || str_contains($component->name, $componentName)) {
                $alternative[] = $component;
            }
        }

        return $alternative;
    }

    private function installComponent(Kit $kit, Component $component, string $destination, bool $force = false): bool
    {
        try {
            $this->io->text(\sprintf('<info>Installing component "%s"...</>', $component->name));

            $this->componentInstaller->install($kit, $component, $destination);
        } catch (ComponentAlreadyExistsException) {
            if ($force) {
                $this->componentInstaller->install($kit, $component, $destination, true);

                return true;
            }

            $this->io->warning(\sprintf('The component "%s" already exists.', $component->name));

            if ($this->isInteractive) {
                if ($this->io->confirm('Do you want to overwrite it?')) {
                    $this->componentInstaller->install($kit, $component, $destination, true);

                    return true;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
