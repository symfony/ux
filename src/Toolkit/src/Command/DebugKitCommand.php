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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:debug-kit',
    description: 'Debug a local Kit.',
    hidden: true,
)]
class DebugKitCommand extends Command
{
    public function __construct(
        private readonly KitFactory $kitFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('kit-path', InputArgument::OPTIONAL, 'The path to the kit to debug', '.')
            ->setHelp(<<<'EOF'
To debug a Kit in the current directory:
    <info>php %command.full_name%</info>

Or in another directory:
    <info>php %command.full_name% ./kits/shadcn</info>
    <info>php %command.full_name% /path/to/my-kit</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitPath = $input->getArgument('kit-path');
        $kitPath = Path::makeAbsolute($kitPath, getcwd());
        $kit = $this->kitFactory->createKitFromAbsolutePath($kitPath);

        $io->title(\sprintf('Kit "%s"', $kit->name));

        $io->definitionList(
            ['Name' => $kit->name],
            ['Homepage' => $kit->homepage],
            ['Authors' => implode(', ', $kit->authors)],
            ['License' => $kit->license],
            new TableSeparator(),
            ['Path' => $kit->path],
        );

        $io->section('Components');
        foreach ($kit->getComponents() as $component) {
            (new Table($io))
                ->setHeaderTitle(\sprintf('Component: "%s"', $component->name))
                ->setHorizontal()
                ->setHeaders([
                    'File(s)',
                    'Dependencies',
                ])
                ->addRow([
                    implode("\n", $component->files),
                    implode("\n", $component->getDependencies()),
                ])
                ->setColumnWidth(1, 80)
                ->setColumnMaxWidth(1, 80)
                ->render();
            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
