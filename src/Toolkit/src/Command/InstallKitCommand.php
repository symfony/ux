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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Component\ComponentInstaller;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Exception\ComponentAlreadyExistsException;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:install-kit',
    description: 'This command will install a full UX Toolkit kit in your project',
)]
class InstallKitCommand extends Command
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
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the kit installation, even if some files already exist')
            ->addOption('kit', 't', InputOption::VALUE_OPTIONAL, 'Override the kit name', $this->kitName)
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command will install a full UX Toolkit kit in your project.

To fully install your current kit, use:

<info>php %command.full_name%</info>

To fully install a kit from an official UX Toolkit kit, use the <info>--kit</info> option:

<info>php %command.full_name% --kit=shadcn</info>

To fully install a kit from an external GitHub kit, use the <info>--kit</info> option:

<info>php %command.full_name% --kit=github.com/user/repository@kit</info>
<info>php %command.full_name% --kit=github.com/user/repository@kit:branch</info>
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

        foreach ($kit->getComponents() as $component) {
            if (!$this->installComponent($kit, $component, 'templates/components')) {
                return Command::FAILURE;
            }
        }

        // Iterate over the component's dependencies
        $phpDependenciesToInstall = [];
        foreach ($component->getDependencies() as $dependency) {
            if ($dependency instanceof ComponentDependency) {
                if (!$this->installComponent($kit, $kit->getComponent($dependency->name), 'templates/components')) {
                    return Command::FAILURE;
                }
            } elseif ($dependency instanceof PhpPackageDependency && !\array_key_exists($dependency->name, $phpDependenciesToInstall) && !InstalledVersions::isInstalled($dependency->name)) {
                $phpDependenciesToInstall[$dependency->name] = $dependency;
            }
        }

        if ([] !== $phpDependenciesToInstall) {
            $this->io->writeln(\sprintf('Run <info>composer require %s</info> to install the required PHP dependencies.', implode(' ', $phpDependenciesToInstall)));
            $this->io->newLine();
        }

        return Command::SUCCESS;
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
