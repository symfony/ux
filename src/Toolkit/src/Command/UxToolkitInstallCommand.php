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
use Symfony\UX\Toolkit\Compiler\Exception\TwigComponentAlreadyExist;
use Symfony\UX\Toolkit\Compiler\TwigComponentCompiler;
use Symfony\UX\Toolkit\ComponentRepository\CurrentTheme;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:install',
    description: 'This command will install a new UX Component in your project',
)]
class UxToolkitInstallCommand extends Command
{
    public function __construct(
        private readonly CurrentTheme $currentTheme,
        private readonly RegistryFactory $registryFactory,
        private readonly TwigComponentCompiler $compiler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::REQUIRED, 'The component name (Ex: button) or repository URL')
            ->addOption(
                'destination',
                'd',
                InputArgument::OPTIONAL,
                'The destination directory',
                'templates/components'
            )
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite the component if it already exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->currentTheme->getIdentity();

        $io->info(
            \sprintf(
                'Downloading medata for %s/%s..',
                $repository->getVendor(),
                $repository->getPackage(),
            )
        );

        $name = ucfirst($input->getArgument('component'));
        $finder = $this->currentTheme->getRepository()->fetch($repository);
        $registry = $this->registryFactory->create($finder);

        if (!$registry->has($name)) {
            $io->error(\sprintf('The component "%s" does not exist.', $name));

            return Command::FAILURE;
        }
        $component = $registry->get($name);

        $destination = $input->getOption('destination');
        try {
            $io->info(\sprintf('Installing component "%s"...', $component->name));
            $this->compiler->compile($registry, $component, $destination);
        } catch (TwigComponentAlreadyExist $e) {

            if ($input->getOption('overwrite')) {
                // again
                $this->compiler->compile($registry, $component, $destination, true);

                $io->success(\sprintf('The component "%s" has been installed.', $component->name));
                return Command::SUCCESS;
            }

            if (!$input->isInteractive()) {
                $io->error(\sprintf('The component "%s" already exists.', $component->name));

                return Command::FAILURE;
            }

            if (!$io->confirm(
                \sprintf('The component "%s" already exists. Do you want to overwrite it?', $component->name)
            )) {
                return Command::FAILURE;
            }

            // again
            $this->compiler->compile($registry, $component, $destination, true);
        }

        $io->success(\sprintf('The component "%s" has been installed.', $component->name));

        return Command::SUCCESS;
    }
}
