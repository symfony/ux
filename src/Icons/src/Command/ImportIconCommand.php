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
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:icons:import',
    description: 'Import icon(s) from iconify.design',
)]
final class ImportIconCommand extends Command
{
    public function __construct(private Iconify $iconify, private LocalSvgIconRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('names', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Icon name from ux.symfony.com/icons (e.g. "mdi:home")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $names = $input->getArgument('names');
        $result = Command::SUCCESS;
        $importedIcons = 0;

        $prefixIcons = [];
        foreach ($names as $name) {
            if (!preg_match('#^([\w-]+):([\w-]+)$#', $name, $matches)) {
                $io->error(\sprintf('Invalid icon name "%s".', $name));
                $result = Command::FAILURE;

                continue;
            }

            [, $prefix, $name] = $matches;
            $prefixIcons[$prefix] ??= [];
            $prefixIcons[$prefix][$name] = $name;
        }

        foreach ($prefixIcons as $prefix => $icons) {
            if (!$this->iconify->hasIconSet($prefix)) {
                $io->error(\sprintf('Icon set "%s" not found.', $prefix));
                $result = Command::FAILURE;

                continue;
            }

            $metadata = $this->iconify->metadataFor($prefix);
            $io->newLine();
            $io->writeln(\sprintf(' Icon set: %s (License: %s)', $metadata['name'], $metadata['license']['title']));

            foreach ($this->iconify->chunk($prefix, array_keys($icons)) as $iconNames) {
                $cursor = new Cursor($output);
                foreach ($iconNames as $name) {
                    $io->writeln(\sprintf('  Importing %s:%s ...', $prefix, $name));
                }
                $cursor->moveUp(\count($iconNames));

                try {
                    $batchResults = $this->iconify->fetchIcons($prefix, $iconNames);
                } catch (\InvalidArgumentException $e) {
                    // At this point no exception should be thrown
                    $io->error($e->getMessage());

                    return Command::FAILURE;
                }

                foreach ($iconNames as $name) {
                    $cursor->clearLineAfter();

                    // If the icon is not found, the value will be null
                    if (null === $icon = $batchResults[$name] ?? null) {
                        $io->writeln(\sprintf(' <fg=red;options=bold>✗</> Not Found <fg=bright-white;bg=black>%s:</><fg=bright-red;bg=black>%s</>', $prefix, $name));

                        continue;
                    }

                    ++$importedIcons;
                    $this->registry->add(\sprintf('%s/%s', $prefix, $name), (string) $icon);
                    $io->writeln(\sprintf(' <fg=bright-green;options=bold>✓</> Imported <fg=bright-white;bg=black>%s:</><fg=bright-magenta;bg=black;options>%s</>', $prefix, $name));
                }
            }
        }

        if ($importedIcons === $totalIcons = \count($names)) {
            $io->success(\sprintf('Imported %d/%d icons.', $importedIcons, $totalIcons));
        } elseif ($importedIcons > 0) {
            $io->warning(\sprintf('Imported %d/%d icons.', $importedIcons, $totalIcons));
        } else {
            $io->error(\sprintf('Imported %d/%d icons.', $importedIcons, $totalIcons));
            $result = Command::FAILURE;
        }

        return $result;
    }
}
