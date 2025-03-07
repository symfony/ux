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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Toolkit\ComponentRepository\CurrentTheme;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
#[AsCommand(
    name: 'debug:ux:toolkit',
    description: 'This command list all components available in the current theme.'
)]
class DebugUxToolkitCommand extends Command
{
    public function __construct(
        private readonly CurrentTheme $currentTheme,
        private readonly RegistryFactory $registryFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->currentTheme->getIdentity();
        $finder = $this->currentTheme->getRepository()->fetch($repository);
        $registry = $this->registryFactory->create($finder);

        $io->title('Current theme:');
        $io->note('Update your config/packages/ux_toolkit.yaml to change the current theme.');
        $io->table(['Vendor', 'Package'], [[$repository->getVendor(), $repository->getPackage()]]);

        $io->title('Available components:');
        $table = [];
        foreach ($registry->all() as $component) {
            $table[] = [$component->name];
        }

        $io->table(['Component'], $table);

        $io->note('Run "symfony console ux:toolkit:install <component>" to install a component.');

        return Command::SUCCESS;
    }
}
