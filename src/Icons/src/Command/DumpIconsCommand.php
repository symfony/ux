<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Icons\JavaScript\IconsDumper;

/**
 * @internal
 */
#[AsCommand(
    name: 'ux:icons:dump',
    description: 'Dump local icons as a JavaScript module',
)]
final class DumpIconsCommand extends Command
{
    public function __construct(private IconsDumper $dumper)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $names = $this->dumper->dump();

        if ($io->isVerbose()) {
            $io->listing($names);
        }

        $io->success(\sprintf('Dumped %d icon(s).', \count($names)));

        return Command::SUCCESS;
    }
}
