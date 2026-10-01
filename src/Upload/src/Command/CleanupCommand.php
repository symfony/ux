<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsCommand(
    name: 'ux:upload:cleanup',
    description: 'Clean up expired completed uploads and stale upload sessions',
)]
final class CleanupCommand extends Command
{
    public function __construct(
        private readonly PrunableStorageInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('age', null, InputOption::VALUE_REQUIRED, 'Minimum age of incomplete sessions to clean (e.g., 24h, 48h)', '24h');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $ageStr */
        $ageStr = $input->getOption('age');
        $ageSeconds = $this->parseAge($ageStr);

        $this->storage->prune($ageSeconds);

        $io->success('Pruning operation completed.');

        return Command::SUCCESS;
    }

    private function parseAge(string $age): int
    {
        if (!preg_match('/^(\d+)([mhdw])$/', $age, $matches)) {
            throw new \InvalidArgumentException('Invalid age format. Use format like "30m", "24h", "2d", or "1w".');
        }

        $value = (int) $matches[1];
        $unit = $matches[2];

        return match ($unit) {
            'm' => $value * 60,
            'h' => $value * 3600,
            'd' => $value * 86400,
            'w' => $value * 604800,
        };
    }
}
