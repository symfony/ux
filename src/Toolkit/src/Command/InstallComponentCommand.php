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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\Installer\Installer;
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
    description: 'Install a new UX Component (e.g. Alert) in your project',
)]
class InstallComponentCommand extends Command
{
    private SymfonyStyle $io;
    private bool $isInteractive;

    public function __construct(
        private readonly string $kitName,
        private readonly RegistryFactory $registryFactory,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'The component name (Ex: Button)')
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

<info>php %command.full_name% Button --kit=https://github.com/user/my-kit</info>
<info>php %command.full_name% Button --kit=https://github.com/user/my-kit:branch</info>
EOF
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitName = $input->getOption('kit');
        $registry = $this->registryFactory->getForKit($kitName);
        $kit = $registry->getKit($kitName);

        if (null === $componentName = $input->getArgument('component')) {
            // Ask for the component name if not provided
            $componentName = $io->choice('Which component do you want to install?', array_map(fn (Component $component) => $component->name, $this->getAvailableComponents($kit)));
            $component = $kit->getComponent($componentName);
        } elseif (null === $component = $kit->getComponent($componentName)) {
            // Suggest alternatives if component does not exist
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

        $io->writeln(\sprintf('Installing component <info>%s</> from the <info>%s</> kit...', $component->name, $kitName));

        $installer = new Installer($this->filesystem, fn (string $question) => $this->io->confirm($question, $input->isInteractive()));
        $installationReport = $installer->installComponent($kit, $component, $destinationPath = $input->getOption('destination'), $input->getOption('force'));

        if ([] === $installationReport->newFiles) {
            $this->io->warning('The component has not been installed.');

            return Command::SUCCESS;
        }

        $this->io->success('The component has been installed.');
        $this->io->writeln('The following file(s) have been added to your project:');
        $this->io->listing(array_map(fn (File $file) => Path::join($destinationPath, $file->relativePathName), $installationReport->newFiles));

        if ([] !== $installationReport->suggestedPhpPackages) {
            $this->io->writeln(\sprintf('Run <info>composer require %s</> to install the required PHP dependencies.', implode(' ', $installationReport->suggestedPhpPackages)));
            $this->io->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<Component>
     */
    private function getAvailableComponents(Kit $kit): array
    {
        $availableComponents = [];

        foreach ($kit->getComponents() as $component) {
            if (str_contains($component->name, ':')) {
                continue;
            }

            $availableComponents[] = $component;
        }

        return $availableComponents;
    }

    /**
     * @return list<Component>
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
}
