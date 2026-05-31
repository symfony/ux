<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Native\ConfigurationBuilder;

#[AsCommand(
    name: 'ux:native:build-configs',
    description: 'Build Hotwire Native configuration files (iOS, Android)',
)]
final class BuildConfigurationsCommand extends Command
{
    public function __construct(
        private readonly ConfigurationBuilder $configurationBuilder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment('Building UX Native configuration files...');

        $this->configurationBuilder->build();
        $io->success('UX Native configuration files built successfully.');

        return Command::SUCCESS;
    }
}
