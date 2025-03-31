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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:lint-kit',
    description: 'Lint a kit, check for common mistakes and ensure the kit is valid.',
    hidden: true,
)]
class LintKitCommand extends Command
{
    public function __construct(
        private readonly RegistryFactory $registryFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('kit', InputArgument::REQUIRED, 'The kit name, can be a local kit (e.g.: "shadcn") or a GitHub kit (e.g.: "https://github.com/user/repository@kit-name").')
            ->setHelp(<<<'EOF'
The kit name can be a local kit (e.g.: "shadcn") or a GitHub kit (e.g.: "https://github.com/user/repository@kit-name").

To lint a local kit:

<info>php %command.full_name% shadcn</info>

To lint a GitHub kit:

<info>php %command.full_name% https://github.com/user/repository@kit-name</info>
<info>php %command.full_name% https://github.com/user/repository@kit-name@v1.0.0</info>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitName = $input->getArgument('kit');
        $registry = $this->registryFactory->getForKit($kitName);
        $kit = $registry->getKit($kitName);

        $io->success(\sprintf('The kit "%s" is valid, it has %d components.', $kit->name, \count($kit->getComponents())));

        return Command::SUCCESS;
    }
}
